<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxRinger
  {

    protected static

      $_instance;

    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
       return self::$_instance;
    } // instance

    public function connect($calleeNumber)
    {
      $this->execute('C,' . PbxSwitch::instance()->getOrdinal($calleeNumber)); 
    } // connect

    public function disconnect()
    {
      $this->execute('D,0');
    } // disconnect

    protected function __construct($params)
    {
      parent::__construct($params->droid);
    } // __conctruct

  } // PbxRinger

?>
