<?php
namespace SnapBill;

/** DefaultFactory looks for classes in the \SnapBill\Objects namespace. */
class DefaultFactory implements Factory {

  protected $namespace = '\\SnapBill\\Objects';

  protected function fullClassName($class) {
    $parts = explode('_', $class);
    $parts = array_map('ucfirst', $parts);
    return $this->namespace.'\\'.implode('', $parts);
  }

  function supportsClass($class) {
    if (strpos($class, '\\') !== false)
      return false;
    $class = $this->fullClassName($class);
    return class_exists($class);
  }

  function create($class) {
    $class = $this->fullClassName($class);
    return new $class();
  }

  function callStatic($class, $method, $args) {
    $class = $this->fullClassName($class);
    return call_user_func_array(array($class, $method), $args);
  }

}
