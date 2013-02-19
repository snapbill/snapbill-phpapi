<?php
namespace SnapBill;

interface Factory {

  /** Returns a boolean value indicating whether or not the factory is able
   * to create instances of the specified class. */
  function supportsClass($class);

  /** Creates a new instance of a class. */
  function create($class);

  function callStatic($class, $method, $args);

}
