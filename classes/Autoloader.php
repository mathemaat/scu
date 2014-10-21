<?php

class Autoloader
{
  static $directories;

  public static function _static() {
    self::$directories = array(
      APPLICATION_PATH . '/classes'
    );
    spl_autoload_register(array(__CLASS__, 'load'));
  }

  public static function load($className) {
    $path = str_replace('_', '/', $className) . '.php';
    foreach (self::$directories as $directory) {
      $filename = $directory . '/' . $path;
      if (file_exists($filename)) {
        require_once $filename;
        if (class_exists($className, false) || interface_exists($className, false))
          return false;
      }
    }
    return true;
  }
}

Autoloader::_static();
