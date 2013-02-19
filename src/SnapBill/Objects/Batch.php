<?php
namespace SnapBill\Objects;
use SnapBill;

class Batch extends SnapBill\Base {

  function setState($state) {
    $this->post('set_state', array('state' => $state));
  }

  static function buildSearch($search) {
    if (isset($search['account'] && is_array($search['account']))) {
      $ids = array_map(function($account) { return $account->id; }, $search['account']);
      $search['account'] = implode(',', $ids);
    }
    if (isset($search['state']) && !is_array($search['state'])) {
      $search['state'] = array($search['state']);
    }
    return $search;
  }

}

