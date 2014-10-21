<?php

class Router
{
  protected static $requestUri;
  protected static $handler;

  public static function getRequestUri() {
    return self::$requestUri;
  }

  public static function getHandler() {
    return self::$handler;
  }

  public static function getRequestUrl() {
    return Util::getAbsoluteUrl(APPLICATION_DOCROOT . '/' . self::$requestUri);
  }

  public static function route() {
    self::$requestUri = isset($_GET['q']) ? $_GET['q'] : '';
    $components = explode('/', self::$requestUri);

    $handler = Util::toCamelCase(array_shift($components));
    if ($handler == null) {
      self::$requestUri = 'index';
      $handler          = 'Index';
    }

    $className = 'Handler_' . $handler;

    // check whether class exists
    if (!class_exists($className) || !is_subclass_of($className, 'Handler'))
      self::notFound();

    // check whether class is abstract
    $reflectionClass = new ReflectionClass($className);
    if ($reflectionClass->isAbstract())
      self::notFound();

    self::$handler = new $className($components);
    self::$handler->handleRequest();
  }

  public static function notFound() {
    header('HTTP/1.1 404 Not Found');
    self::$handler = new Handler_Page(array('404'));
    self::$handler->handleRequest();
    die();
  }

  public static function error($title = null, $message = null)
  {
    header('HTTP/1.1 500 Internal Server Error');
    self::$handler = new Handler_Page(array('500'));
    self::$handler->setTemplateVars(array('errorTitle' => htmlspecialchars($title), 'errorMessage' => htmlspecialchars($message)));
    self::$handler->handleRequest();
    die();
  }
}