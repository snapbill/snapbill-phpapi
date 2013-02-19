<?php
namespace SnapBill;

use \Exception;

class Connection {

  static $CURL_OPTIONS = array(
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_SSL_VERIFYHOST => 2,
  );

  protected $url;
  protected $headers = array();
  protected $username;
  protected $password;

  function __construct($options=array()) {

    $host = 'api.snapbill.com';
    if (isset($options['host'])) $host = $options['host'];
    $secure = true;
    if (isset($options['secure'])) $secure = $options['secure'];

    $this->url = ($secure ? 'https' : 'http').'://'.$host.'/v1';

    if (isset($options['username']) && isset($options['password'])) {
      $this->username = $options['username'];
      $this->password = $options['password'];
    } else {
      // Attempt to load from user's .snapbill.cfg file
      $config = $_SERVER['HOME'].'/.snapbill.cfg';
      if (isset($options['config'])) $config = $options['config'];
      list($this->username, $this->password) = self::loadAuthFromConfig($config);
    }

    if (isset($options['headers'])) {
      $this->headers = $options['headers'];
    }

  }

  function post($action, $args=array()) {
    $curl = $this->initializeCurl($action, $args);
    $result = curl_exec($curl);
    if ($result === false) {
      $error = curl_error($curl);
      curl_close($curl);
      throw new Exception($error);
    }
    $result = json_decode($result, true);

    if ($result['code'] != 200) {
      throw new Exception("Received code ".$result['code']." from SnapBill");
    }
    return $result;
  }

  protected function initializeCurl($action, $args) {
    $url = $this->url.'/'.$action.'.json';
    $curl = curl_init($url);

    curl_setopt_array($curl, self::$CURL_OPTIONS);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // Add headers in the form "key: value"
    $headers = array();
    foreach ($this->headers as $key => $value) {
      $headers[] = $key.': '.$value;
    }
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    curl_setopt($curl, CURLOPT_USERPWD, $this->username.':'.$this->password);

    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $this->encodeParams($args));

    return $curl;
  }

  private function encodeParams($params) {
    // Extract nested 'data' parameters
    if (isset($params['data'])) {
      $data = $params['data'];
      unset($params['data']);
      foreach ($data as $k => $v) {
        $params["data-$k"] = $v;
      }
    }
    // Encode to string
    $str = '';
    foreach ($params as $k => $v) {
      $str .= '&'.$this->encodeParam($k, $v);
    }
    return substr($str, 1);
  }
  private function encodeParam($k, $v) {
    if (is_array($v)) {
      // Flatten array parameters
      $elems = array_map(function($elem) use ($k) {
        return urlencode($k).'[]='.urlencode($elem);
      }, $v);
      return implode('&', $elems);
    } else {
      return urlencode($k).'='.urlencode($v);
    }
  }

  private static function loadAuthFromConfig($file) {
    $config = parse_ini_file($file, true);
    $api = $config['api'];
    return array($api['user'], $api['password']);
  }

}
