<?php

  class PbxCall
  {
  
    protected
	
	  $callerNumber,
	  $calleeNumber;
	  
	public function __construct($params)
	{
	  $this->callerNumber = $params->callerNumber;
	  $this->calleeNumber = $params->calleeNumber;
	} // __construct
 
    public function __get($prop)
	{
	  return (property_exists($this, $prop)) ? $this->$prop : null;
	} // __get
 
  } // PbxCall

?>