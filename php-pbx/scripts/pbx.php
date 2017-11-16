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

  $cache  = new Cache();

  $pbx    = Pbx::instance();

  $fabric = PbxSwitch::instance();

  $tone   = PbxCallProgressTone::instance();

  $ringer = PbxRinger::instance();

  $finder = PbxLineFinder::instance();

  $digits = PbxDigitReceiver::instance();

  while (true) {
    $tone->update();
    $ringer->update();
    $finder->update();
    $digits->update();
    $linesOffHook = $finder->lines;
    for ($i = 0; $i < 8; $i++) {
      if (($station = $pbx->getStation($i)) === null) {
        continue;
      }
      if (in_array($i, $linesOffHook)) {
        if ($station->isBusy()) {
          switch ($station->status) {
            case PbxStation::STATUS_ON_HOOK:
              $station->setStatus(PbxStation::STATUS_OFF_HOOK);
              break;
            case PbxStation::STATUS_OFF_HOOK:
              if ($tone->status == PbxCallProgressTone::STATUS_READY) {
                $tone->connect($station->ordinal);
                $station->setStatus(PbxStation::STATUS_DIALING);
              }
              break;
            case PbxStation::STATUS_DIALING:
              switch ($digits->status) {
                case PbxDigitReceiver::STATUS_READY:
                  break;
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
                  break;
                case PbxDigitReceiver::STATUS_TIMED_OUT:
                  break;
              }
              break;
            case PbxStation::STATUS_RINGING:
              $ringer->disconnect();
              $caller = $tone->getStation();
              $tone->disconnect();
              $fabric->connect($caller, $i);
              break;
            case PbxStation::STATUS_TALKING:
              break;
            case PbxStation::STATUS_WET_LIST:
              break;
          }
        }
        else {

        }
      }
      else {

      }
    }

  }

?>
