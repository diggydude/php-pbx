<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxDigitReceiver extends Droid
  {

    const STATUS_READY     = 0;
    const STATUS_WAITING   = 1;
    const STATUS_RECEIVING = 2;
    const STATUS_COMPLETED = 3;
    const STATUS_TIMED_OUT = 4;

    protected static

      $_instance;

    protected

      $status,
      $lastUpdated,
      $timeout,
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
      $now = time();
      switch ($this->status) {
        case self::STATUS_READY:
          break;
        case self::STATUS_WAITING:
          $number = $this->execute('G,0');
          $len    = strlen($number);
          if ($len > 0) {
            $this->status = self::STATUS_RECEIVING;
          }
          if ($len > strlen($this->number)) {
            $this->number      = $number;
            $this->lastUpdated = $now;
          }
          if (($now - $this->lastUpdated) > $this->timeout) {
            $this->status = self::STATUS_TIMED_OUT;
          }
          break;
        case self::STATUS_RECEIVING:
          $number = $this->execute('G,0');
          $len    = strlen($number);
          if ($len > strlen($this->number)) {
            $this->number      = $number;
            $this->lastUpdated = $now;
          }
          if ($len == 4) {
            $this->status = self::STATUS_COMPLETED;
            break;
          }
          if (($now - $this->lastUpdated) > $this->timeout) {
            $this->status = self::STATUS_TIMED_OUT;
          }
          break;
        case self::STATUS_COMPLETED:
        case self::STATUS_TIMED_OUT:
          break;
      }
    } // update

    public function connect($station)
    {
      if (($station = Pbx::instance()->getStation($station)) === null) {
        throw new Exception(__METHOD__ . ' > Invalid station identifier.');
      }
      $this->execute('C,' . $station->ordinal);
      $this->status = self::STATUS_WAITING;
    } // connect

    public function disconnect()
    {
      $this->execute('D,0');
      $this->reset();
    } // disconnect

    public function reset()
    {
      $this->status = self::STATUS_READY;
      $this->lastUpdated = time();
      $this->number      = null;
    } // reset

    protected function __construct($params)
    {
      parent::__construct($params->droid);
      $this->timeout = (isset($params->timeout)) ? $params->timeout : 15;
      $this->reset();
    } // __construct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // Droid

?>