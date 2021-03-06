<?php
namespace SnapBill\Objects;
use SnapBill;

class Client extends SnapBill\Base {

  function addUser($data) {
    $result = $this->post('add_user', $data);
    return $this->connection->load('user', $result);
  }

  function addService($data) {
    $result = $this->post('add_service', $data);
    return $this->connection->load('service', $result);
  }

  function addInvoice($data, $lines) {
    // Merge $lines into $data
    foreach ($lines as $line) {
      foreach ($line as $k => $v) {
        $data[$k][] = $v;
      }
    }
    $result = $this->post('add_invoice', $data);
    return $this->connection->load('invoice', $result);
  }

  function setPayment($data) {
    return $this->post('set_payment', $data);
  }

  function lostPassword() {
    $result = $this->post('lost_password');
    return $this->connection->load('email', $result);
  }

  protected function wrapWithObjects($data) {
    if (isset($data['services'])) {
      $services = array();
      foreach ($data['services'] as $id => $service_data) {
        $services[$id] = $this->connection->load('service', $service_data);
      }
      $data['services'] = $services;
    }
    return $data;
  }
  protected function unwrappedFields() {
    return array('email', 'payment');
  }

}
