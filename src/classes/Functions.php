<?php

namespace amteich\Twig;

class Functions {

  public static function error ($msg, $source = null, $context = null) {
    $sourceObj = null;
    if ($source != "") {
      $sourceObj = new \Twig\Source('', $source);
    }
    throw new \Twig\Error\Error($msg . '<br>' . $context, -1, $sourceObj);
  }

}
