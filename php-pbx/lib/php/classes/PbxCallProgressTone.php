<?php

  require_once(__DIR__ . '/Pbx.php');
  require_once(__DIR__ . '/Droid.php');

  class PbxCallProgressTone
  {

    const TONE_DIAL    = 0;
    const TONE_RINGING = 1;
    const TONE_BUSY    = 2;
    const TONE_REORDER = 3;
    const STATUS_READY = 0;
    const STATUS_BUSY  = 1;
	
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
	
    public function connect($station)
    {
      $this->status  = self::STATUS_BUSY;
      $this->station = Pbx::instance()->getStation($station)->ordinal;
      $this->setTone(self::TONE_DIAL);
      return $this->droid->execute('CONNECT ' . $this->station);
    } // connect

    public function disconnect()
    {
      $response      = $this->droid->execute('DISCONNECT');
      $this->station = null;
      $this->status  = self::STATUS_READY;
      return $response;
    } // disconnect
	
    public function setTone($tone)
    {
      switch ($tone) {
        case self::TONE_DIAL:
        case self::TONE_RINGING:
        case self::TONE_BUSY:
          $response = $this->droid->execute('TONE ' . $tone);
          break;
        // When the reorder tone is set, the generator should be
        // available for reuse next time it's requested.
        case self::TONE_REORDER:
          $response = $this->droid->execute('TONE ' . self::TONE_REORDER);
          $this->status = self::STATUS_READY;
          break;
        default:
          throw new Exception(__METHOD__ . ' > Unknown tone: ' . $tone);		
      }
      return $response;
    } // setTone

    public function mute()
    {
      return $this->droid->execute('MUTE');
    } // mute

    protected function __construct($params)
    {
      $this->droid   = new Droid($params->droid);
      $this->mute();
      $this->station = null;
      $this->status  = self::STATUS_READY;
    } // __construct
  
  } // PbxCallProgressTone

?>
