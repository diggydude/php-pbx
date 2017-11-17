<?php

#             An Internet of Things (IoT) Framework in PHP
#
#                    Copyright 2017 James Elkins
#
# This software is released under the Pay It Forward License (PIFL) with
# neither express nor implied warranty as regards merchantablity or fitness
# for any particular use. The end user assumes responsibility for all
# consequences arising from the use of this software.
#
# Use of this software in whole or in in part for any commercial purpose,
# including use in undistributed in-house applications, obligates the user
# to "Pay It Forward" by contributing monetarily or in kind to such open
# source software and/or hardware project(s) as the user may choose.
#
# This software may be freely copied and distributed as long as said copies
# are accompanied by this copyright notice and licensing agreement. This
# document shall cosntitute the entirety of the agreement between the
# software's author and the end user.

  require_once(__DIR__ . '/Serial.php');

  class Droid
  {

    protected static $droids = array();

    protected

      $id,
      $timeout,
      $tty;

    public static function scan($params)
    {
      foreach (glob($params->pattern) as $device) {
        $tty = new Serial(
                 (object) array(
                    'device'   => $device,
                    'baudRate' => $params->baudRate,
                    'lineEnd'  => $params->lineEnd,
                    'type'     => 'arduino'
                 )
               );
        $tty->write('ID?');
        $droidId = $tty->read($params->timeout);
        $tty->close();
        if (strlen($droidId) == 0) {
          echo __METHOD__ . " > Device at $device is not a Droid.\n";
          continue;
        }
        echo __METHOD__ . " > Found Droid \"" . $droidId . "\" at " . $device . "\n";
        self::$droids[$droidId] = $device;
      }
      echo __METHOD__ . " > " . count(self::$droids) . " Droid(s) found.\n";
      return self::$droids;
    } // scan

    public function __construct($params)
    {
      $this->id      = $params->id;
      $this->timeout = $params->timeout;
      $this->tty     = new Serial(
                         (object) array(
                           'device'   => $params->device,
                           'baudRate' => $params->baudRate,
                           'lineEnd'  => $params->lineEnd,
                           'type'     => 'arduino'
                         )
                       );
    } // __construct

    public function execute($command)
    {
      $this->tty->write($command);
      $response = $this->tty->read($this->timeout);
      if (strlen($response) == 0) {
        echo __METHOD__ . " > No response.\n";
        return null;
      }
      return $response;
    } // esecute

    public function close()
    {
      $this->tty->close();
      $this->id      = "";
      $this->timeout = 0;
      $this->tty     = null;
    } // close

  } // Droid

?>

