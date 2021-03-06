<?php
namespace SnapBill;

abstract class Base {

  protected $identity;
  protected $class;
  protected $connection;
  protected $depth;
  protected $data = array();

  function __construct($identity, $class, $connection) {
    $this->identity = $identity;
    $this->class = $class;
    $this->connection = $connection;
    $this->depth = 1000;
  }

  function update($data) {
    $result = $this->post('update', $data);
    $this->gather($result, true);
  }

  // Gathers fetched data from the API into the object.
  function gather($data, $overwrite=false) {
    if (isset($data['depth'])) {
      $this->depth = min($this->depth, (int)($data['depth']));
      unset($data['depth']);
    }

    $data = $this->wrapWithObjects($data);

    if (!$overwrite) {
      // Check for conflicting fields
      foreach ($data as $key => $value) {
        if (isset($this->data[$key]) && $this->data[$key] !== $value) {
          throw new Exception("Gathered data for ".$this->class.'.'.$key." (".$value." does not match existing value ".$this->data[$key]);
        }
      }
    }

    foreach ($data as $field => $value) {
      $this->data[$field] = $value;
    }
  }

  // Fetches data from the API and gathers it.
  function fetch() {
    assert($this->depth > 0);

    $result = $this->post('get');

    assert($result['type'] === 'item');
    assert($result['class'] === $this->class);
    assert(is_array($result[$this->class]));

    $this->gather($result[$this->class]);
  }

  private function getField($key) {
    if (isset($this->data[$key])) {
      return $this->data[$key];
    }
    if ($this->depth > 0) {
      $this->fetch();
      if (isset($this->data[$key])) {
        return $this->data[$key];
      }
    }
    return NULL;
  }

  function __isset($key) {
    return ($this->getField($key) !== NULL);
  }

  function __get($key) {
    $value = $this->getField($key);
    if ($value !== NULL) return $value;
    throw new Exception("Unknown variable '$key' for SnapBill ".$this->class." object");
  }

  protected function wrapWithObjects($data) {
    $unwrapped = $this->unwrappedFields();
    foreach ($data as $field => $value) {
      // Check if $field is a SnapBill class
      if (!in_array($field, $unwrapped) && $this->connection->supportsClass($field)) {
        $object = $this->connection->load($field, $value);
        $data[$field] = $object;
      }
    }
    return $data;
  }

  protected function unwrappedFields() {
    return array();
  }

  protected function post($action, $args=array()) {
    $action = $this->class.'/'.$this->identity.'/'.$action;
    return $this->connection->http->post($action, $args);
  }

  static function buildSearch($search) {
    return $search;
  }

}
