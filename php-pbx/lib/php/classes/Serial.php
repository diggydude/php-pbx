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

  class Serial
  {

    protected

      $device,
      $baudRate,
      $dataBits,
      $stopBits,
      $parity,
      $localEcho,
      $lineEnd,
      $command,
      $handle,
      $type;

    public function __construct($params)
    {
      if (!file_exists($params->device)) {
        throw new Exception(__METHOD__ . ' > Device "' . $params->device . '" not found.');
      }
      if (!isset($params->baudRate)) {
        $params->baudRate = 9600;
      }
      if (!isset($params->dataBits)) {
        $params->dataBits = 8;
      }
      if (!isset($params->stopBits)) {
        $params->stopBits = 1;
      }
      if (!isset($params->parity)) {
        $params->parity = "none";
      }
      if (!isset($params->localEcho)) {
        $params->localEcho = "off";
      }
      if (!isset($params->lineEnd)) {
        $params->lineEnd = "\r\n";
      }
      if (!isset($params->type)) {
        $params->type = "terminal";
      }
      $this->device   = $params->device;
      $this->baudRate = $params->baudRate;
      if ($params->dataBits < 5) $params->dataBits = 5;
      if ($params->dataBits > 8) $params->dataBits = 8;
      $this->dataBits = "cs" . $params->dataBits;
      if ($params->stopBits < 1) $params->stopBits = 1;
      if ($params->stopBits > 2) $params->stopBits = 2;
      $this->stopBits = ($params->stopBits == 1) ? "-cstopb" : "cstopb";
      switch ($params->parity) {
        case "none":
        default:
          $this->parity = "-parenb";
        case "even":
          $this->parity = "parenb -parodd";
        case "odd":
          $this->parity = "parenb parodd";
      }
      if (!in_array(strtolower($params->type), array("terminal", "arduino"))) {
        throw new Exception(__METHOD__ . " > Unsupported type: " . $params->type);
      }
      $this->type      = $params->type;
      $this->localEcho = (in_array(strtolower($params->localEcho), array("on", "yes", true))) ? "echo" : "-echo";
      $this->lineEnd   = $params->lineEnd;
      if (strtolower($this->type) == "arduino") {
        $this->command = "/bin/stty -F " . $this->device . " " . $this->baudRate . " " . $this->dataBits
                       . " " . $this->stopBits . " " . $this->localEcho . " ignbrk -brkint -icrnl -imaxbel -opost"
                       . " -onlcr -isig -icanon -iexten -echo -echoe -echok -echoctl -echoke noflsh -ixon -crtscts";
      }
      else {
        $this->command = "/bin/stty -F " . $this->device . " " . $this->baudRate .  " sane raw " . $this->dataBits
                       . " " . $this->stopBits . " hupcl cread clocal " . $this->localEcho . " -onlcr";
      }
      $errorStr        = exec($this->command, $output, $exitCode);
      if (intval($exitCode) !== 0) {
        throw new Exception(__METHOD__ . ' > Initialization failure: ' . $errorStr);
      }
      if (($this->handle = fopen($this->device, "c+")) === false) {
        throw new Exception(__METHOD__ . ' > Could not open device "' . $this->device . '".');
      }
    } // __construct

    public function setLineEnd($lineEnd)
    {
      $this->lineEnd = $lineEnd;
    } // setLineEnd

    public function write($data)
    {
      if (!is_resource($this->handle)) {
        throw new Exception(__METHOD__ . ' > Not connected.');
      }
      stream_set_blocking($this->handle, 1);
      if (($numBytes = fwrite($this->handle, trim($data) . $this->lineEnd)) === false) {
        throw new Exception(__METHOD__ . ' > Could not write to device.');
      }
      return $numBytes;
    } // write

    public function read($timeout = null)
    {
      if (!is_resource($this->handle)) {
        throw new Exception(__METHOD__ . ' > Not connected.');
      }
      stream_set_blocking($this->handle, 0);
      $line = "";
      if ($timeout != null) {
        $ttl = time() + $timeout;
      }
      do {
        if (($timeout !== null) && (time() >= $ttl)) {
          break;
        }
        $char = fgetc($this->handle);
        if ($char === false) {
          usleep(50000);
          continue;
        }
        $line .= $char; 
      } while ($char != $this->lineEnd{0});
      return trim($line);
    } // read

    public function readByte($timeout = null)
    {
      if (!is_resource($this->handle)) {
        throw new Exception(__METHOD__ . ' > Not connected.');
      }
      stream_set_blocking($this->handle, 0);
      $byte = "";
      do {
        if (($timeout !== null) && (time() >= $ttl)) {
          break;
        }
        $byte = fgetc($this->handle);
        if ($byte === false) {
          usleep(50000);
          continue;
        } 
      } while ($byte != $this->lineEnd{0});
      return $byte;
    } // readByte

    public function close()
    {
      @fclose($this->handle);
    } // close

    public function __get($prop)
    {
      return (isset($this, $prop)) ? $this->$prop : null;
    } // __get

  } // Serial

?>
