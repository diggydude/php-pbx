<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxDigitReceiver
  {

    const STATUS_READY     = 0;
    const STATUS_WAITING   = 1;
    const STATUS_RECEIVING = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_TIMED_OUT = 4;

    protected static

      $_instance;

    protected

      $droid,
      $status,
      $lastUpdated,
      $timeout,
      $station,
      $number;

    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
      return self::$_instance();
    } // instance

    public function update()
    {
      $status = $this->droid->execute('STATUS?');
      switch ($this->status) {
        case self::STATUS_READY:
          $this->status = $status;
          break;
        case self::STATUS_WAITING:
          if ($this->status == self::STATUS_READY) {
            $this->lastUpdated = time();
          }
          $this->status = ((time() - $this->lastUpdated) > $this->timeout) ? self::STATUS_TIMED_OUT : $status;
          break;
        case self::STATUS_RECEIVING:
          if ($this->status == self::STATUS_WAITING) {
            $this->lastUpdated = time();
          }
          $digits = $this->droid->execute('DIGITS?');
          if ($digits > $this->number) {
            $this->number      = $digits;
            $this->lastUpdated = time();
          }
          $this->status = ((time() - $this->lastUpdated) > $this->timeout) ? self::STATUS_TIMED_OUT : $status;
          break;
        case self::STATUS_COMPLETED:
          $this->number = $this->droid->execute('DIGITS?');
          $this->status = $status;
          break;
        case self::STATUS_TIMED_OUT:
          $this->droid->execute('DISCONNECT');
          $this->reset();
          break;
      }
    } // update

    public function connect($station)
    {
      if (($station = Pbx::instance()->getStation($station)) === null) {
        throw new Exception(__METHOD__ . ' > Invalid station identifier.');
      }
      $this->station = $station->ordinal;
      $this->droid->execute('CONNECT ' . $this->station);
      $this->lastUpdated = time();
      $this->status      = self::STATUS_WAITING;
    } // connect

    public function disconnect()
    {
      $this->droid->execute('DISCONNECT');
      $this->reset();
    } // disconnect

    public function reset()
    {
      $this->droid->execute('RESET');
      $this->station     = null;
      $this->lastUpdated = time();
      $this->number      = null;
      $this->status      = self::STATUS_READY;
    } // reset

    protected function __construct($params)
    {
      $this->droid   = new Droid($params->droid);
      $this->timeout = (isset($params->timeout)) ? $params->timeout : 15;
      $this->reset();
    } // __construct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxDigitReceiver

?>
