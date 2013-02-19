<?php
namespace SnapBill;

class DefaultLoader implements Loader {

  private $namespace;

  function __construct($namespace) {
    $this->namespace = $namespace;
  }

  function phpName($class) {
    $parts = explode('_', $class);
    $parts = array_map('ucfirst', $parts);
    return $this->namespace.'\\'.implode('', $parts);
  }

}
