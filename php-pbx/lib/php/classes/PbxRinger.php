<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxRinger
  {

    const STATUS_READY     = 0;
    const STATUS_RINGING   = 1;
    const STATUS_TIMED_OUT = 2;

    protected static

      $_instance;

    protected

      $droid,
      $status,
      $lastUpdated,
      $timeout,
      $station;

    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
       return self::$_instance;
    } // instance

    public function connect($station)
    {
      $this->station = Pbx::instance()->getStation($station)->ordinal;
      $this->droid->execute('CONNECT ' . $this->station);
      $this->status      = self::STATUS_RINGING;
      $this->lastUpdated = time();
    } // connect

    public function disconnect()
    {
      $this->droid->execute('DISCONNECT');
      $this->reset();
    } // disconnect

    public function update()
    {
      switch ($this->status) {
        case self::STATUS_READY:
        case self::STATUS_TIMED_OUT;
          break;
        case self::STATUS_RINGING:
          if ((time() - $this->lastUpdated) > $this->timeout) {
            $this->status = self::STATUS_TIMED_OUT;
          }
          break;
      }
    } // update

    public function reset()
    {
      $this->status      = self::STATUS_READY;
      $this->lastUpdated = time();
      $this->station     = null;
    } // reset

    protected function __construct($params)
    {
      $this->droid = new Droid($params->droid);
      $this->reset();
    } // __conctruct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxRinger

?>
