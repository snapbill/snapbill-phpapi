<?php
namespace SnapBill\Objects;
use SnapBill;

class User extends SnapBill\Base {

  static function login($connection, $username, $password) {
    $users = $connection->search(array('username' => $username, 'password' => $password));
    if (count($users) > 1) {
      throw new Exception("Failure during login (received multiple users)");
    } else if ($users) {
      return $users[0];
    } else {
      return NULL;
    }
  }

}

