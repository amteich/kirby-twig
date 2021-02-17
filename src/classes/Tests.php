<?php

namespace amteich\Twig;

class Tests {
  public static function of_type ($var, $typeTest, $className = '') {
    switch ($typeTest)
    {
      default:
        return false;
        break;

      case 'array':
        return is_array($var);
        break;

      case 'bool':
        return is_bool($var);
        break;

      case 'class':
        return is_object($var) === true && get_class($var) === $className;
        break;

      case 'float':
        return is_float($var);
        break;

      case 'int':
        return is_int($var);
        break;

      case 'numeric':
        return is_numeric($var);
        break;

      case 'object':
        return is_object($var);
        break;

      case 'scalar':
        return is_scalar($var);
        break;

      case 'string':
        return is_string($var);
        break;
    }
  }
}
