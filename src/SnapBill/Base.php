<?php
namespace SnapBill;

abstract class Base {

  protected $class;
  protected $context;
  protected $depth;
  protected $data = array();

  function init($class, $context) {
    $this->class = $class;
    $this->context = $context;
    $this->depth = 1000;
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

  // Fetches data from the API and gather()s it.
  function fetch() {
    assert($this->depth > 0);
    $data = $this->post('get');
    $data = $data[$this->class];
    $this->gather($data);
  }

  function update($data) {
    $result = $this->post('update', $data);
    $this->gather($result, true);
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
    return null;
  }

  function __isset($key) {
    return ($this->getField($key) !== null);
  }

  function __get($key) {
    $value = $this->getField($key);
    if ($value !== null) return $value;
    throw new Exception("Unknown variable '$key' for SnapBill ".$this->class." object");
  }

  protected function wrapWithObjects($data) {
    $unwrapped = $this->unwrappedFields();
    foreach ($data as $field => $value) {
      // Check if $field is a SnapBill class
      if (!in_array($field, $unwrapped) && $this->context->supportsClass($field)) {
        $object = $this->context->load($field, $value);
        $data[$field] = $object;
      }
    }
    return $data;
  }

  protected function unwrappedFields() {
    return array();
  }

  protected function post($action, $args=array()) {
    if (isset($this->xid)) {
      $id = $this->xid;
    } else if (isset($this->id)) {
      $id = $this->id;
    } else if (isset($this->code)) {
      $id = $this->code;
    } else {
      throw new Exception("Could not find id for ".$this->class." object");
    }
    $action = $this->class.'/'.$id.'/'.$action;
    return $this->context->conn->post($action, $args);
  }

  static function buildSearch($search) {
    return $search;
  }

}