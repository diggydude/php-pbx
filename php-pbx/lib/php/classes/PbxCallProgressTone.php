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
	
	  $instance;
	
	protected
	
	  $tone;
	
	public static function instance($params = null)
	{
	  if (!(self::$instance instanceof self)) {
		self::$instance = new self($params);
	  }
	  return self::$instance;
	} // instance
	
	public function connectTo($stationNumber)
	{
	  
	} // connectTo
	
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
	
	public function release()
	{
	  $this->setTone(self::TONE_NONE);
	} // release
  
    protected function __construct($params)
	{
	  parent::__construct($params);
	  $this->tone = self::TONE_NONE;
	} // __construct
  
  } // PbxCallProgressTone

?>