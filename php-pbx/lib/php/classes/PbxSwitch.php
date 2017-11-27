<?php

  require_once(__DIR__ . '/Droid.php');
  require_once(__DIR__ . '/Pbx.php');

  class PbxSwitch
  {

    protected static

      $_instance;

    protected

      $cache,
      $droid,
      $routes;

    public static function instance($params = null)
    {
      if (!(self::$_instance instanceof self)) {
        self::$_instance = new self($params);
      }
       return self::$_instance;
    } // instance

    public function connect($caller, $callee)
    {
      $route = $this->getRoute($caller, $callee);
      $this->droid->execute('CONNECT ' . $route->ax . ' ' . $route->ay);
      $this->markRouteBusy($route);
    } // connect

    public function disconnect($caller, $callee)
    {
      $route = $this->getRoute($caller, $callee);
      $this->droid->execute('DISCONNECT ' . $route->ax . ' ' . $route->ay);
      $this->markRouteNotBusy($route);
    } // disconnect

    public function findConnection($station)
    {
      $station = Pbx::instance()->getStation($station);
      if ($station === null) {
        throw new Exception(__METHOD__ . ' > Invalid station identifier.');
      }
      foreach ($this->routes as $caller => $callees) {
        foreach ($callees as $callee => $connected) {
          if ((($caller == $station->ordinal) || ($callee == $station->ordinal)) && ($connected == 1)) {
            return (object) array('ax' => $caller, 'ay' => $callee);
          }
        }
      }
      return null;
    } // findConnection

    public function getRoute($caller, $callee)
    {
      $caller = Pbx::instance()->getStation($caller);
      $callee = Pbx::instance()->getStation($callee);
      if (($caller === null) || ($callee === null)) {
        throw new Exception(__METHOD__ . ' > Invalid station identifier.');
      }
      if ($caller->ordinal == $callee->ordinal) {
        throw new Exception(__METHOD__ . ' > Route from a station to itself not permitted.');
      }
      return (object) array('ax' => $caller->ordinal, 'ay' => $callee->ordinal);
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

    protected function __construct($params)
    {
      $this->droid = new Droid($params->droid);
      if (!($params->cache instanceof Cache)) {
        throw new Exception(__METHOD__ . ' > Instance of Cache required.');
      }
      $this->cache = $params->cache;
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

  } // PbxSwitch

?>
