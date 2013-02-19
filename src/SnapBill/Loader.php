<?php
namespace SnapBill;

interface Loader {

  /** Returns the fully-qualified (ie. with namespaces) PHP class name for a snapbill class. */
  function phpName($snapbillClass);

}
