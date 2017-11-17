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

  $config = json_decode(file_get_contents(__DIR__ . '/pbx.json'));

  $cache  = Cache::connect(
              (object) array(
                'id'      => $config->cache->id,
                'ttl'     => $config->cache->ttl,
                'servers' => $config->cache->servers
              );
            );

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
    $tone->update();
    $ringer->update();
    $finder->update();
    $digits->update();
    for ($i = 0; $i < 8; $i++) {
      if (($station = $pbx->getStation($i)) === null) {
        continue;
      }
      $offHook = in_array($i, $finder->lines);
      if ($offfHook) {
        switch ($station->status) {
          case PbxStation::STATUS_ON_HOOK:
            $station->setStatus(PbxStation::STATUS_OFF_HOOK);
            break;
          case PbxStation::STATUS_OFF_HOOK:
            if (($tone->status == PbxCallProgressTone::STATUS_READY)
                   && ($digits->status == PbxDigitReceiver::STATUS_READY)) {
              $tone->connect($station->ordinal);
              $digits->connect($station->ordinal);
              $station->setStatus(PbxStation::STATUS_DIALING);
            }
            break;
          case PbxStation::STATUS_DIALING:
            switch ($digits->status) {
              case PbxDigitReceiver::STATUS_READY:
              case PbxDigitReceiver::STATUS_WAITING:
                break;
              case PbxDigitReceiver::STATUS_RECEIVING:
                $tone->setTone(PbxCallProgressTone::TONE_NONE);
                break;
              case PbxDigitReceiver::STATUS_COMPLETED:
                $number = $digits->number;
                $digits->disconnect();
                if (($callee = $pbx->getStation($number)) === null) {
                  $tone->setTone(PbxCallProgressTone::TONE_REORDER);
                  break;
                }
                $ringer->connect($callee->ordinal);
                $tone->setTone(PbxCallProgressTone::TONE_RINGING);
                $station->setStatus(PbxStation::STATUS_CONNECTING);
                break;
              case PbxDigitReceiver::STATUS_TIMED_OUT:
                $digits->disconnect();
                $tone->setTone(PbxCallProgressTone::TONE_REORDER);
                $station->setStatus(PbxStation::STATUS_OFF_HOOK);
                break;
            }
            break;
          case PbxStation::STATUS_RINGING:
            $ringer->disconnect();
            $caller = $tone->station();
            $tone->disconnect();
            $fabric->connect($caller, $i);
            break;
          case PbxStation::STATUS_CONNECTING:
            if ($ringer->status == PbxRinger::STATUS_TIMED_OUT) {
              $ringer->disconnect();
              $tone->disconnect();
              $station->setStatus(PbxStation::STATUS_OFF_HOOK);
            }
            break;
          case PbxStation::STATUS_TALKING:
          case PbxStation::STATUS_WET_LIST:
            break;
        }
      }
      else {
        switch ($station->status) {
          case PbxStation::STATUS_ON_HOOK:
            break;
          case PbxStation::STATUS_RINGING:
            if ($ringer->status == PbxRinger::STATUS_TIMED_OUT) {
              $ringer->disconnect();
              $pbx->getStation($tone->station)->setStatus(PbxStation::STATUS_OFF_HOOK);
              $tone->disconnect();
              $station->setStatus(PbxStation::STATUS_ON_HOOK);
            }
            break;
          case PbxStation::STATUS_OFF_HOOK:
          case PbxStation::STATUS_WET_LIST:
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
          case PbxStation::STATUS_DIALING:
            $tone->disconnect();
            $digits->disconnect();
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
          case PbxStation::STATUS_CONNECTING:
            $ringer->disconnect();
            $tone->disconnect();
            $station->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
          case PbxStation::STATUS_TALKING:
            $route = $fabric->getConnection($station->ordinal);
            $fabric->disconnect($route);
            $pbx->getStation($route->ax)->setStatus(PbxStation::STATUS_ON_HOOK);
            $pbx->getStation($route->ay)->setStatus(PbxStation::STATUS_ON_HOOK);
            break;
        }
      } // if/else
    } // for
  } // while

?>
