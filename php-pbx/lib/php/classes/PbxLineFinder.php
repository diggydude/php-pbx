<?php

  require_once(__DIR__ . '/Droid.php');

  class PbxLineFinder
  {

    const LINE_ON_HOOK  = false;
    const LINE_OFF_HOOK = true;

    protected static

      $_instance;

    protected

      $droid,
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
      $status      = $this->droid->execute('STATUS?');
      $this->lines = array();
      for ($i = 0; $i < 8; $i++) {
        $this->lines[] = (($status >> i) == 1)
                       ? self::LINE_OFF_HOOK
                       : self::LINE_ON_HOOK;
      }
    } // update

    public function lineIsOffHook($line)
    {
      return ($this->lines[$line] == self::LINE_OFF_HOOK);
    } // lineIsOffHook

    protected function __construct($params)
    {
      $this->droid = new Droid($params->droid);
      $this->lines = array();
    } // __construct

    public function __get($prop)
    {
      return (property_exists($this, $prop)) ? $this->$prop : null;
    } // __get

  } // PbxLineFinder

?>
