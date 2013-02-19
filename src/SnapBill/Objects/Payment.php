<?php
namespace SnapBill\Objects;
use SnapBill;

class Payment extends SnapBill\Base {

  static function buildSearch($search) {
    if (isset($search['client']) && is_object($search['client'])) {
      $search['client_id'] = $search['client']->id;
      unset($search['client']);
    }
    return $search;
  }

}

