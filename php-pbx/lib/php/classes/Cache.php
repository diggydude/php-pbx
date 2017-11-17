<?php

#             An Internet of Things (IoT) Framework in PHP
#
#                    Copyright 2017 James Elkins
#
# This software is released under the Pay It Forward License (PIFL) with
# neither express nor implied warranty as regards merchantablity or fitness
# for any particular use. The end user assumes responsibility for all
# consequences arising from the use of this software.
#
# Use of this software in whole or in in part for any commercial purpose,
# including use in undistributed in-house applications, obligates the user
# to "Pay It Forward" by contributing monetarily or in kind to such open
# source software and/or hardware project(s) as the user may choose.
#
# This software may be freely copied and distributed as long as said copies
# are accompanied by this copyright notice and licensing agreement. This
# document shall cosntitute the entirety of the agreement between the
# software's author and the end user.

  require_once(__DIR__ . '/../functions/string.php');

  class Cache
  {

    protected static $caches = array();

    protected

      $id,
      $memcache,
      $servers,
      $ttl;

    public static function connect($params)
    {
      if (is_string($params)) {
        $id = $params;
        if (isset(self::$caches[$id])) {
          return self::$caches[$id];
        }
        throw new Exception(__METHOD__ . ' > No cache with ID "' . $id . '" exists.');
      }
      $cache = new self($params);
      self::$caches[$params->id] = $cache;
      return $cache;
    } // connect

    public static function disconnect($id)
    {
      if (!isset(self::$caches[$id])) {
        return false;
      }
      unset(self::$caches[$id]);
      return true;
    } // disconnect

    public function set($key, $val)
    {
      if ($this->memcache->setByKey($this->id, $key, $val, time() + $this->ttl) === false) {
        throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
      }
    } // set

    public function get($key)
    {
      if (($val = $this->memcache->getByKey($this->id, $key)) === false) {
        if ($this->memcache->getResultCode() !== Memcached::RES_SUCCESS) {
          throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
        }
      }
      return $val;
    } // set

    public function delete($key)
    {
      if ($this->memcache->deleteByKey($this->id, $key) === false) {
        $err = $this->memcache->getResultMessage();
        if (stripos($err, 'NOT FOUND') !== false) {
          return false;
        }
        throw new Exception(__METHOD__ . ' > ' . $err); 
      }
      return true;
    } // delete{

    public function exists($key)
    {
      try {
        $val = $this->get($key);
        return true;
      }
      catch (Exception $e) {
        $err = $e->getMessage();
        if (stripos($err, 'NOT FOUND') === false) {
          throw new Exception(__METHOD__ . ' > ' . $err);
        }
        return false;
      }
    } // exists

    public function keys()
    {
      if (($keys = $this->memcache->getAllKeys()) === false) {
        throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
      }
      return $keys; 
    } // keys

    public function flush($delay = 0)
    {
      if ($this->memcache->flush($delay) === false) {
        throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
      }
    } // flush

    public function stats()
    {
      if (($stats = $this->memcache->getStats()) === false) {
        throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
      }
      return $stats;
    } // stats

    protected function __construct(stdClass $params)
    {
      $this->id      = (isset($params->id))
                     ? $params->id
                     : randomString(32);
      $this->servers = $params->servers;
      $this->ttl     = (isset($params->ttl))
                     ? $params->ttl
                     : 300;
      $options       = array(
                         Memcached::OPT_LIBKETAMA_COMPATIBLE => true,
                         Memcached::OPT_PREFIX_KEY           => $this->id
                       );
      if (isset($params->options) && is_array($params->options)) {
        $options = array_merge($options, $params->options);
      }
      $this->memcache = new Memcached($this->id);
      if ($this->memcache->setOptions($options) === false) {
        throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
      }
      if (!count($this->memcache->getServerList())) {
        if ($this->memcache->addServers($this->servers) === false) {
          throw new Exception(__METHOD__ . ' > ' . $this->memcache->getResultMessage());
        }
      }
    } // __construct

    public function __destruct()
    {
      $this->memcache->quit();
      $this->id       = "";
      $this->servers  = array();
      $this->ttl      = 0;
      $this->memcache = null;
    } // __destruct

  } // Cache

?>
