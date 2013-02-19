<?php
namespace SnapBill;

/**
 * A Context is the root of all interactions with the SnapBill API.
 */
class Context {

  // The Connection to use for posting to the API.
  public $conn;

  private $loaders;
  private $cache;

  /**
   * Creates a new SnapBill Context. See the constructor of Connection for a list of valid options.
   */
  function __construct($options=array()) {
    $this->conn = is_array($options) ? new Connection($options) : $options;
    $this->loaders = array(new DefaultLoader('\\SnapBill\\Objects'));
    $this->cache = new Cache();
  }

  /**
   * Returns a SnapBill object of a given class. $data may either be an array of class-specific variables,
   * or an xid, id or code that identifies the object. The object will be cached so that future calls to load
   * with the same identifier will return the same object.
   */
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
      $data = NULL;
    }

    $object = $this->getObject($class, $id);
    if ($data) $object->gather($data);
    return $object;
  }

  /**
   * Returns an array of objects of a given class that match a search query.
   */
  function search($class, $search=array()) {
    $phpClass = $this->getPHPClass($class, true);
    $search = $phpClass::buildSearch($search);
    $results = $this->conn->post("$class/list", $search)['list'];
    return array_map(function($data) use ($class) {
      return $this->load($class, $data);
    }, $results);
  }

  /**
   * Adds a new object.
   */
  function add($class, $data) {
    $result = $this->conn->post("$class/add", $data);
    return $this->load($class, $result);
  }

  function addLoader($loader) {
    array_unshift($this->loaders, $loader);
  }

  function supportsClass($class) {
    return ($this->getPHPClass($class, false) !== NULL);
  }

  // Loads a cached object or creates and caches a new one.
  private function getObject($class, $id) {
    $obj = $this->cache->get($class, $id);
    if (!$obj) {
      $obj = $this->createNew($class, $id);
      $this->cache->store($class, $id, $obj);
    }
    return $obj;
  }

  // Instantiates a new object without checking the cache.
  private function createNew($class, $id) {
    $phpClass = $this->getPHPClass($class, true);
    $object = new $phpClass($id, $class, $this);
    return $object;
  }

  private function getPHPClass($class, $raiseException) {
    foreach ($this->loaders as $loader) {
      $phpClass = $loader->phpName($class);
      if (class_exists($phpClass)) return $phpClass;
    }
    if ($raiseException)
      throw new Exception("Unknown SnapBill class: $class");
    return NULL;
  }

}
