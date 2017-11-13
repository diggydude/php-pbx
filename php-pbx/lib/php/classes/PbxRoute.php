<?php

  require_once(__DIR__ . '/Cache.php');

  class PbxRoute
  {

    protected

      $cache,
      $stations,
      $routes;
      
    public function __construct($params)
    {
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
          if (!is_int($number) || (strlen((string) $number) !== 4)) {
            throw new Exception(__METHOD__ . ' > Number must be a 4-digit integer.');
          }
          $this->stations[$number] = $ordinal;
        }
        $this->cache->set('stations', $stations);
      }
      $this->stations = $this->cache->get('stations');
      if (!$this->cache->exists('routes')) {
        $routes = array();
        for ($x = 0; $x < 8; $x++) {
          $routes[$x] = array();
          for ($y = 0; $y < 8; $y++) {
            $routes[$x][$y] = ($y == $x) ? -1 : 0;
          }
        }
        $this->cache->set('routes', $routes);
      }
      $this->routes = $this->cache->get('routes');
    } // __construct

    public function getRoute($callerNumber, $calleeNumber)
    {
      if ($callerNumber == $calleeNumber) {
        throw new Exception(__DIR__ . ' > Route from a station to itself not permitted.');
      }
      return (object) array(
               'ax' => $this->stations[$callerNumber],
               'ay' => $this->stations[$calleeNumber]
             );
    } // getRoute

    public function markRouteBusy($route)
    {
      $this->routes[$route->ax][$route->ay] = 1;
      $this->cache->set('routes', $this->routes);
    } // markRouteBusy

    public function markRouteNotBusy($route)
    {
      $this->routes[$route->ax][$route->ay] = 0;
      $this->cache->set('routes', $this->routes);
    } // markRouteNotBusy

    public function routeIsBusy($route)
    {
      return ($this->routes[$route->ax][$route->ay] == 1);
    } // routeIsBusy

  } // PbxRoute

?>
