<?php
namespace SnapBill;

class Cache {

  private $cache;

  function __construct() {
    $this->cache = array();
  }

  private function &getClass($class) {
    if (isset($this->cache[$class])) {
      $cache =& $this->cache[$class];
    } else {
      $cache = array();
      $this->cache[$class] =& $cache;
    }
    return $cache;
  }

  function get($class, $id) {
    $class =& $this->getClass($class);
    return isset($class[$id]) ? $class[$id] : null;
  }

  function store($class, $id, $object) {
    $class =& $this->getClass($class);
    $class[$id] = $object;
  }

}
