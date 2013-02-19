<?php
namespace SnapBill;

class Xid {

  /** Returns a two-element array containing the account ID and object ID from an XID. */
  static function decode($xid) {
    if (!self::isValid($xid)) {
      throw new Exception("Invalid SnapBill XID: '$xid'");
    }
    list($account, $id) = explode(':', $xid);
    return array(self::decodePart($account), self::decodePart($id));
  }

  /** Creates an XID string from an account ID and an object ID. */
  static function encode($account_id, $object_id) {
    $account = self::encodePart($account_id);
    $object = self::encodePart($object_id);
    return $account.':'.$object;
  }

  static function isValid($xid) {
    return !!preg_match('/^[0-9A-Za-z_-]+:[0-9A-Za-z_-]+$/', $xid);
  }

  // Left-pads $part with character $char to make the length a multiple of $multiple.
  private static function pad($part, $multiple, $char) {
    $n = strlen($part) % $multiple;
    if ($n > 0) {
      for ($i = $n; $i < $multiple; $i++) {
        $part = $char.$part;
      }
    }
    return $part;
  }

  private static function decodePart($part) {
    $part = str_replace('-', '+', $part);
    $part = str_replace('_', '/', $part);
    $part = self::pad($part, 4, 'A');
    $decoded = base64_decode($part);
    $decoded = self::pad($decoded, 4, "\0");
    $unpacked = unpack('Nnum', $decoded);
    return $unpacked['num'];
  }

  private static function encodePart($num) {
    $data = pack('N', $num);
    $data = self::pad($data, 3, "\0");
    $encoded = base64_encode($data);
    $encoded = str_replace('+', '-', $encoded);
    $encoded = str_replace('/', '_', $encoded);
    return ltrim($encoded, 'A');
  }

}
