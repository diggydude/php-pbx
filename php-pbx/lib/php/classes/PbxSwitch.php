<?php

  require_once(__DIR__ . '/Droid.php');
  require_once(__DIR__ . '/Pbx.php');

  class PbxSwitch extends Droid
  {

    protected static

      $_instance;

    protected

      $cache,
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
      $this->markRouteBusy($route);
      $this->execute('C,' . $route->ax . ',' . $route->ay);
    } // connect

    public function disconnect($caller, $callee)
    {
      $route = $this->getRoute($caller, $callee);
      $this->markRouteNotBusy($route);
      $this->execute('D,' . $route->ax . ',' . $route->ay);
    } // disconnect

    public function getRoute($caller, $callee)
    {
      $ax = Pbx::instance()->getStation($caller)->ordinal;
      $ay = Pbx::instance()->getStation($callee)->ordinal;
      if (($ax === null) || ($ay === null)) {
        throw new Exception(__METHOD__ . ' > Invalid station identifier.');
      }
      if ($ax == $ay) {
        throw new Exception(__METHOD__ . ' > Route from a station to itself not permitted.');
      }
      return (object) array('ax' => $ax, 'ay' => $ay);
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
      parent::__construct($params->droid);
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
