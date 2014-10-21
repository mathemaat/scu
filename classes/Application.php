<?php

require_once 'classes/Autoloader.php';

class Application
{
  private static $instance;

  private function __construct() {
    /* Setup PHP session */
    session_name('scu');
    session_start();

    set_exception_handler(array(__CLASS__, 'handleException'));
  }

  public static function getInstance() {
    if (is_null(self::$instance))
      self::$instance = new self();

    return self::$instance;
  }

  public static function handleException($exception) {
    Router::error(get_class($exception), $exception->getMessage());
  }
}
