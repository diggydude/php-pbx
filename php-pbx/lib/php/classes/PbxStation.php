<?php

  class PbxStation
  {
  
    protected
	
	  $number,
	  $busy;

	public function __construct($params)
	{
      $this->number = $params->number;
	  $this->busy   = $false;
	} // __construct

  } // PbxStation

?>