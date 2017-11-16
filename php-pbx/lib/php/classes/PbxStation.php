<?php

  class PbxStation
  {

    const STATUS_ON_HOOK    = 0;
    const STATUS_OFF_HOOK   = 1;
    const STATUS_DIALING    = 2;
    const STATUS_CONNECTING = 3;
    const STATUS_RINGING    = 4;
    const STATUS_TALKING    = 5;
    const STATUS_WET_LIST   = 6;
  
    protected
	
      $number,
      $ordinal,
      $status;

    public function __construct($params)
    {
      $this->number  = $params->number;
      $this->ordinal = $params->ordinal;
      $this->status  = self::STATUS_ON_HOOK;
    } // __construct

    public function setStatus($status)
    {
      if (($status < self::STATUS_ON_HOOK) || ($status > self::STATUS_WET_LIST)) {
        throw new Exception(__METHOD__ . ' > Invalid status.');
      }
      $this->status = $status;
    } // setStatus

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxStation

?>
