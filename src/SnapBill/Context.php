<?php
namespace SnapBill;

class Context {

  public $conn;

  private $loaders;
  private $cache;

  function __construct($options=array()) {
    $this->conn = is_array($options) ? new Connection($options) : $options;
    $this->loaders = array(new DefaultLoader('\\SnapBill\\Objects'));
    $this->cache = new Cache();
  }

  function addLoader($loader) {
    array_unshift($this->loaders, $loader);
  }

  function load($class, $data) {

    // Extract id from $data
    if (is_array($data)) {
      if (isset($data['id'])) {
        $id = $data['id'];
      } else if (isset($data['xid'])) {
        list($account, $id) = Xid::decode($data['xid']);
      } else if (isset($data['code'])) {
        $id = $data['code'];
      } else {
        throw new Exception("Cannot find id, xid or code in provided data");
      }
    } else {
      if (Xid::isValid($data)) {
        list($account, $id) = Xid::decode($data);
      } else {
        $id = $data;
      }
      $data = null;
    }

    $object = $this->getObject($class, $id);
    if ($data) $object->gather($data);
    return $object;
  }

  function search($class, $search=array()) {
    $phpClass = $this->getPHPClass($class, true);
    $search = $phpClass::buildSearch($search);
    $results = $this->conn->post("$class/list", $search)['list'];
    return array_map(function($data) use ($class) {
      return $this->load($class, $data);
    }, $results);
  }

  function add($class, $data) {
    $result = $this->post("$class/add", $data);
    return $this->load($class, $result);
  }

  function supportsClass($class) {
    return ($this->getPHPClass($class, false) !== null);
  }

  // Loads a cached object or creates and caches a new one.
  private function getObject($class, $id) {
    $obj = $this->cache->get($class, $id);
    if (!$obj) {
      $obj = $this->createNew($class);
      $this->cache->store($class, $id, $obj);
    }
    return $obj;
  }

  // Instantiates a new object without checking the cache.
  private function createNew($class) {
    $phpClass = $this->getPHPClass($class, true);
    $object = new $phpClass();
    $object->init($class, $this);
    return $object;
  }

  private function getPHPClass($class, $raiseException) {
    foreach ($this->loaders as $loader) {
      $phpClass = $loader->phpName($class);
      if (class_exists($phpClass)) return $phpClass;
    }
    if ($raiseException)
      throw new Exception("Unknown SnapBill class: $class");
    return null;
  }

}
