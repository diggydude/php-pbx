<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxRinger extends Droid
  {

    const STATUS_READY     = 0;
    const STATUS_RINGING   = 1;
    const STATUS_TIMED_OUT = 2;

    protected static

      $_instance;

    protected

      $status,
      $lastUpdated,
      $timeout;

    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
       return self::$_instance;
    } // instance

    public function connect($station)
    {
      $this->execute('C,' . Pbx::instance()->getStation($station)->ordinal);
      $this->status      = self::STATUS_RINGING;
      $this->lastUpdated = time();
    } // connect

    public function disconnect()
    {
      $this->execute('D,0');
      $this->reset();
    } // disconnect

    public function update()
    {
      switch ($this->status) {
        case self::STATUS_READY:
        case self::STATUS_TIMED_OUT;
          break;
        case self::STATUS_RINGING:
          $now = time();
          if (($now - $this->lastUpdated) > $this->timeout) {
            $this->status = self::STATUS_TIMED_OUT;
          }
          break;
      }
    } // update

    public function reset()
    {
      $this->status      = self::STATUS_READY;
      $this->lastUpdated = time();
    } // reset

    protected function __construct($params)
    {
      parent::__construct($params->droid);
      $this->reset();
    } // __conctruct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxRinger

?>
