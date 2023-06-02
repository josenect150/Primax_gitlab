<?php

namespace Drupal\co_150_core\Service;

/**
 * General class manager.
 */
class General {

  /**
   * GenerateRandomString.
   */
  public function generateRandomString($length = 10, $onlydigits = FALSE) {
    if ($onlydigits) {
      $characters = '0123456789';
    }
    else {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    }
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

}
