<?php

  require_once(__DIR__ . '/Pbx.php');
  require_once(__DIR__ . '/Droid.php');

  class PbxDaa
  {

    const STATUS_ON_HOOK    = 0;
    const STATUS_OFF_HOOK   = 1;
    const STATUS_RINGING    = 2;

    protected static
	
      $_instance;
	
    protected

      $droid,	
      $status,
      $station;
	
    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
       return self::$_instance;
    } // instance

    public function update()
    {
      $this->status = $this->droid->execute('STATUS?');
    } // update

    public function connect($station)
    {
      if (($station = Pbx::instance()->getStation($station)) === null) {
        throw new Exception(__METHOD__ . ' > Invalid station identifier.');
      }
      $this->station = $station->ordinal;
      $this->droid->execute('CONNECT ' . $this->station);
    } // connect

    public function disconnect()
    {
      $this->droid->execute('DISCONNECT');
      $this->station = null;
      $this->status = self::STATUS_ON_HOOK;
    } // disconnect

    protected function __construct($params)
    {
      $this->droid   = new Droid($params->droid);
      $this->station = null;
      $this->status  = self::STATUS_ON_HOOK;
    } // __construct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxDaa

?>
