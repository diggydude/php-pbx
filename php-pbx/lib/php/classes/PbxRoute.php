<?php

  require_once(__DIR__ . '/Cache.php');

  class PbxRoute
  {

    protected

      $stations,
      $busyRoutes;
      
    public function __construct($params)
    {
      if (!($params->cache instanceof Cache)) {
        throw new Exception(__METHOD__ . ' > Instance of Cache required.');
      }
      if (!$cache->exists('stations')) {
        if (!file_exists($params->ConfigFile)) {
          throw new Exception(__METHOD__ . ' > Configuration file "' . $params->configFile . '" not found.');
        }
        $stations   = array();
        $lines      = file($params->configFile);
        $crosspoint = 0;
        $connection = 0;
        foreach ($lines as $line) {
          list($ordinal, $number) = explode("\t", trim($line));
          if (($ordinal < 0) || ($ordinal > 7)) {
            throw new Exception(__METHOD__ . ' > Ordinal must be between 0 and 7.');
          }
          if (!is_int($number) || (strlen((string) $number) !== 4)) {
            throw new Exception(__METHOD__ . ' > Number must be a 4-digit integer.');
          }
          if ($ordinal < 4) {
            $crosspoint = 0;
            $connection = $ordinal;
          }
          else {
            $crosspoint = 1;
            $connection = $ordinal - 4;
          }  
          $this->stations[$number] = (object) array(
                                       'crosspoint' => $crosspoint,
                                       'connection' => $connection
                                     );
        }
        $cache->set('stations', $stations);
      }
      $this->stations = $cache->get('stations');
      if (!$cache->exists('busyRoutes')) {
        $busyRoutes = (object) array(
                        'ingress' => array(),
                        'middle'  => array(),
                        'egress'  => array()
                      );
        $cache->set('busyRoutes', $busyRoutes);
      }
      $this->busyRoutes = $cache->get('busyRoutes');
    } // __construct

    protected function getEndoint($number)
    {
      return $this->stations[$number];
    } // getEndpoint

    protected function findRoute($callerNumber, $calleeNumber)
    {
      $endpoint     = $this->getEndpoint($callerNumber);
      $ingressRoute = null;
      for ($i = 0; $i < 4; $i++) {
        $route = array($endpoint->crosspoint, $endpoint->connection, $i);
        $hash  = implode("_", $route);
        if (!in_array($hash, $this->busyRoutes->ingress)) {
          $this->busyRoutes->ingress[] = $hash;
          $ingressRoute                = $route;
          break;
        }
      }
      if ($ingressRoute == null) {
        return false;
      }
      $crosspoint  = $ingressRoute[0];
      $output      = $ingressRoute[2];
      $connection  = $crosspoint;
      $crosspoint  = $output;
      $middleRoute = null;
      for ($i = 0; $i < 2; $i++) {
        $route = array($crosspoint, $connection, $i);
        $hash  = implode("_", $route);
        if (!in_array($hash, $this->busyRoutes->middle)) {
          $this->busyRoutes->middle[] = $hash;
          $middleRoute                = $route;
          break;
        }
      }
      if ($middleRoute == null) {
        array_pop($this->busyRoutes->ingress);
        return false;
      }
      $endpoint    = $this->getEndpoint($calleeNumber);
      $output      = $endpoint->connection;
      $crosspoint  = $endpoint->crossPoint;
      $input       = $middleRoute[0];
      $egressRoute = null;
      $route       = array($crosspoint, $input, $output);
      $hash        = implode("_", $route);
      if (!in_array($hash, $this->busyRoutes->egress)) {
        $this->busyRoutes->egress[] = $hash;
        $egressRoute                = $route;
      }
      if ($egressRoute == null) {
        array_pop($this->busyRoutes->ingress);
        array_pop($this->busyRoutes->middle);
        return false;
      }
      $route = (object) array(
                 'ingress' => $ingressRoute,
                 'middle'  => $middleRoute,
                 'egress'  => $egressRoute
               );
      return $route;
    } // findRoute
    
    protected function mapIngressControlSignal($params)
    {
      
    } // mapIngressControlSignal
    
    protected function mapMiddleControlSignal($params)
    {
      
    } // mapMiddleControlSignal
    
    protected function mapEgressControlSignal($params)
    {
      
    } // mapEgressControlSignal
    
  } // PbxRoute

?>
