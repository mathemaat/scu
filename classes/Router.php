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
    // splits de uri van de opgevraagde pagina op basis van '/',
    // zodat we stap voor stap kunnen uitlezen welke pagina de gebruiker heeft opgevraagd
    self::$requestUri = isset($_GET['q']) ? $_GET['q'] : '';
    $components = explode('/', self::$requestUri);

    if (count($components) >= 1 && strlen($components[0]) >= 1) {
      $handler = Util::toCamelCase($components[0]);

      // als de opgevraagde pagina een menuitem betreft, bewaar dan nog even welke dit was
      if (self::isMenuItem($handler))
        $handler = 'PostList';
      else
        array_shift($components);
    }
    else {
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

  protected static function isMenuItem($token) {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT mni_id ' .
      'FROM tblmenuitem ' .
      'WHERE mni_token = ?';
    $params = array($token);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_COLUMN);
  }

  public static function allowUpload() {
    // to do: controleren of een gebruiker is ingelogd ipv een hardcoded IP te gebruiken
    $whitelist = array('213.10.71.43');
    return $_SERVER['HTTP_HOST'] == 'localhost' || in_array(Util::getIPAddress(), $whitelist);
  }

  public static function notFound() {
    header('HTTP/1.1 404 Not Found');
    self::$handler = new Handler_Static(array('404'));
    self::$handler->handleRequest();
    die();
  }

  public static function error($title = null, $message = null)
  {
    header('HTTP/1.1 500 Internal Server Error');
    self::$handler = new Handler_Static(array('500'));
    self::$handler->setTemplateVars(array('errorTitle' => htmlspecialchars($title), 'errorMessage' => htmlspecialchars($message)));
    self::$handler->handleRequest();
    die();
  }
}