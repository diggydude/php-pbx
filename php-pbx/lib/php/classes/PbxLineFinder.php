<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxLineFinder extends Droid
  {

    protected static

      $_instance;

    protected

      $lines;

    public function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
      return self::$_instance;
    } // instance

    public function update()
    {
      $lines = $this->execute('G');
      $this->lines = (strlen($lines) > 0)
                   ? explode(",", $lines)
                   : array();
    } // update

    protected function __construct($params)
    {
      parent::__construct($params->droid);
      $this->lines = array();
    } // __construct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxLineFinder

?>
