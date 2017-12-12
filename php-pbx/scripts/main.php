<?php

  require_once(__DIR__ . '/../lib/php/classes/Cache.php');
  require_once(__DIR__ . '/../lib/php/classes/Droid.php');
  require_once(__DIR__ . '/../lib/php/classes/PbxStation.php');
  require_once(__DIR__ . '/../lib/php/classes/Pbx.php');
  require_once(__DIR__ . '/../lib/php/classes/PbxSwitch.php');
  require_once(__DIR__ . '/../lib/php/classes/PbxCallProgressTone.php');
  require_once(__DIR__ . '/../lib/php/classes/PbxRinger.php');
  require_once(__DIR__ . '/../lib/php/classes/PbxLineFinder.php');
  require_once(__DIR__ . '/../lib/php/classes/PbxDigitReceiver.php');

  $config = json_decode(file_get_contents(__DIR__ . '/config.json'));

  $cache  = Cache::connect($config->cache);

  $pbx    = Pbx::instance(
              (object) array(
                'cache'    => $cache,
                'stations' => get_object_vars($config->stations)
              )
            );

  $fabric = PbxSwitch::instance(
              (object) array(
                'cache' => $cache,
                'droid' => $config->fabric->droid
              )
            );

  $tone   = PbxCallProgressTone::instance(
              (object) array(
                'droid' => $config->progressTone->droid
              )
            );

  $ringer = PbxRinger::instance(
              (object) array(
                'timeout' => $config->ringer->timeout,
                'droid'   => $config->ringer->droid
              )
            );

  $finder = PbxLineFinder::instance(
              (object) array(
                'droid' => $config->lineFinder->droid
              )
            );

  $digits = PbxDigitReceiver::instance(
              (object) array(
                'timeout' => $config->digitReceiver->timeout,
                'droid'   => $config->digitReceiver->droid
              )
            );

  while (true) {
    $finder->update();
    for ($i = 0; $i < 8; $i++) {
      if (($station = $pbx->getStation($i)) === null) {
        // No station is registered for that line, so skip
        continue;
      }
      if ($finder->lineIsOffHook($i)) {
        // Station is off hook
        switch ($station->status) {
          case PbxStation::STATUS_ON_HOOK:
            // On-hook station went off hook, so update status
            $station->setStatus(PbxStation::STATUS_OFF_HOOK);
            break;
          case PbxStation::STATUS_OFF_HOOK:
            // Station was already off hook, so initiate call
            // if resources are available
            $digits->update();
            if (($tone->status == PbxCallProgressTone::STATUS_READY)
                   && ($digits->status == PbxDigitReceiver::STATUS_READY)) {
              $tone->disconnect(); // Pre-empt reorder tone
              $tone->connect($station->ordinal);
              $digits->connect($station->ordinal);
              $station->setStatus(PbxStation::STATUS_DIALING);
            }
            break;
          case PbxStation::STATUS_DIALING:
            // Station is placing a call, so see how that's going
            $digits->update();
            switch ($digits->status) {
              case PbxDigitReceiver::STATUS_READY:
              case PbxDigitReceiver::STATUS_WAITING:
                // Wait for station to finish dialing the number
                break;
              case PbxDigitReceiver::STATUS_RECEIVING:
                // First digit has been dialed, so turn off dial tone
                $tone->mute();
                break;
              case PbxDigitReceiver::STATUS_COMPLETED:
                // All four digits have been dialed, so try the call
                $number = $digits->number;
                $digits->disconnect();
                if (($callee = $pbx->getStation($number)) === null) {
                  // Called number doesn't exist
                  $tone->setTone(PbxCallProgressTone::TONE_REORDER);
                  break;
                }
                // Called number exists, so ring the called station
                $ringer->connect($callee->ordinal);
                $tone->setTone(PbxCallProgressTone::TONE_RINGING);
                $station->setStatus(PbxStation::STATUS_CONNECTING);
                break;
              case PbxDigitReceiver::STATUS_TIMED_OUT:
                // Caller took too long to dial number, so timeout
                $digits->disconnect();
                $tone->setTone(PbxCallProgressTone::TONE_REORDER);
                $station->setStatus(PbxStation::STATUS_OFF_HOOK);
                break;
            }
            break;
          case PbxStation::STATUS_RINGING:
            // Called station went off hook, so connect the call
            $ringer->disconnect();
            $caller = $tone->station();
            $tone->disconnect();
            $fabric->connect($caller, $i);
            break;
          case PbxStation::STATUS_CONNECTING:
            // Waiting for called station to answer
            $ringer->update();
            if ($ringer->status == PbxRinger::STATUS_TIMED_OUT) {
              // Called station took too long to answer, so timeout
              $ringer->disconnect();
              $tone->disconnect();
              $station->setStatus(PbxStation::STATUS_OFF_HOOK);
            }
            break;
          case PbxStation::STATUS_TALKING:
          case PbxStation::STATUS_WET_LIST:
            // Station is either on a call or got blacklisted for
            // being off hook without doing anything for too long
            break;
        }
      }
      else {
        // Station is on hook
        switch ($station->status) {
          case PbxStation::STATUS_ON_HOOK:
            // Station was already on hook, so don't do anything
            break;
          case PbxStation::STATUS_RINGING:
            // Station is being rung
            $ringer->update();
            if ($ringer->status == PbxRinger::STATUS_TIMED_OUT) {
              // Called station took too long to answer, so timeout
              $ringer->disconnect();
              $pbx->getStation($tone->station)->setStatus(PbxStation::STATUS_OFF_HOOK);
              $tone->setTone(PbxCallProgressTone::TONE_REORDER);
              $station->setStatus(PbxStation::STATUS_ON_HOOK);
            }
            break;
          case PbxStation::STATUS_OFF_HOOK:
          case PbxStation::STATUS_WET_LIST:
            // Off-hook station went back on hook
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
          case PbxStation::STATUS_DIALING:
            // Station went back on hook while dialing, so abort call attempt
            $tone->disconnect();
            $digits->disconnect();
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
          case PbxStation::STATUS_CONNECTING:
            // Station went back on hook while waiting for called station to answer, so abort
            $ringer->disconnect();
            $tone->disconnect();
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
          case PbxStation::STATUS_TALKING:
            // Station was on a call, then went on hook, so tear down the call
            $route = $fabric->findConnection($station->ordinal);
            $fabric->disconnect($route->ax, $route->ay);
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
        }
      } // if/else
    } // for
  } // while

?>
