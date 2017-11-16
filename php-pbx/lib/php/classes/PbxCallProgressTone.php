<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxCallProgressTone extends Droid
  {

    const TONE_NONE    = 0;
    const TONE_DIAL    = 1;
    const TONE_RINGING = 2;
    const TONE_BUSY    = 3;
    const TONE_REORDER = 4;
	
    protected static
	
      $_instance;
	
    protected
	
      $tone,
      $busy;
	
    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
	self::$_instance = new self($params);
      }
       return self::$_instance;
    } // instance
	
    public function connect($station)
    {
      $this->busy = true;
      $this->setTone(self::TONE_DIAL);
      $this->execute('C,' . Pbx::instance()->getStation($station)->ordinal);
    } // connect

    public function disconnect()
    {
      $this->setTone(self::TONE_NONE);
      $this->execute('D,0');
      $this->busy = false;
    } // disconnect
	
    public function setTone($tone)
    {
      switch ($tone) {
	case self::TONE_NONE:
	case self::TONE_DIAL:
	case self::TONE_RINGING:
	case self::TONE_BUSY:
	case self::TONE_REORDER:
          $this->execute('S,' . $tone);
          break;
        default:
          throw new Exception(__METHOD__ . ' > Unknown tone: ' . $tone);		
      }
    } // setTone

    public function isBusy()
    {
      return $this->busy;
    } // isBusy

    protected function __construct($params)
    {
      parent::__construct($params->droid);
      $this->tone = self::TONE_NONE;
      $this->busy = false;
    } // __construct
  
  } // PbxCallProgressTone

?>
