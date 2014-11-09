<?php

require_once 'classes/Autoloader.php';

class Application
{
  private static $instance;
  private static $dbHandler;

  private function __construct() {
    /* Setup PHP session */
    session_name('scu');
    session_start();

    set_exception_handler(array(__CLASS__, 'handleException'));

    self::$dbHandler = new PDO('mysql:host='.MYSQL_HOST.';dbname='.MYSQL_DATABASE, MYSQL_USERNAME, MYSQL_PASSWORD);
  }

  public static function getInstance() {
    if (is_null(self::$instance))
      self::$instance = new self();

    return self::$instance;
  }
  
  public function getDBHandler() {
     return self::$dbHandler;
  }

  public static function handleException($exception) {
    Router::error(get_class($exception), $exception->getMessage());
  }
}
