<?php

  require_once(__DIR__ . '/Cache.php');

  class Pbx
  {

    protected static

      $_instance;

    protected

      $stations;

    public function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
      return self::$_instance;
    } // instance

    public function getStation($station)
    {
      return (isset($this->stations[$station])) ? $this->stations[$station] : null;
    } // getTStaionByORidnal

    protected function __construct($params)
    {
      $this->stations = array();
      if (!($params->cache instanceof Cache)) {
        throw new Exception(__METHOD__ . ' > Instance of Cache required.');
      }
      $this->cache = $params->cache;
      if (!$this->cache->exists('stations')) {
        if (!file_exists($params->ConfigFile)) {
          throw new Exception(__METHOD__ . ' > Configuration file "' . $params->configFile . '" not found.');
        }
        $stations = array();
        $lines    = file($params->configFile);
        foreach ($lines as $line) {
          list($ordinal, $number) = explode("\t", trim($line));
          if (($ordinal < 0) || ($ordinal > 7)) {
            throw new Exception(__METHOD__ . ' > Ordinal must be between 0 and 7.');
          }
          if (!preg_mtach($number, '/^[2-9]{1}\d{3}$/')) {
            throw new Exception(__METHOD__ . ' > Number must be a 4-digit integer, and may not start with 0 or 1.');
          }
          $station = new PbxStation(
                       (object) array(
                         'number'  => $number,
                         'ordinal' => $ordinal,
                         'status'  => PbxStation::STATUS_ON_HOOK
                       )
                     );
          $stations[$ordinal] = $station;
          $stations[$number]  = $station;
        }
        $this->cache->set('stations', $stations);
      }
      $this->stations = $this->cache->get('stations');      
    } // __construct

  } // Pbx

?>
