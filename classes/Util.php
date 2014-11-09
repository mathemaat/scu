<?php

class Util
{
  public static function debug($var) {
    echo '<pre>' . htmlspecialchars(print_r($var, true)) . '</pre>';
  }

  public static function getIPAddress() {
    return $_SERVER['REMOTE_ADDR'];
  }

  public static function toCamelCase($string) {
    $string = str_replace('_', ' ', $string);
    $string = ucwords(strtolower($string));
    $string = str_replace(' ', '', $string);

    return $string;
  }

  public static function getTemplate($template) {
    /* load HTML for template file */
    $html = file_get_contents(APPLICATION_PATH . "/static/" . $template . ".html");

    if ($html === false)
      throw new Exception("Unable to get template contents (template = " . $template . ")");

    return $html;
  }

  public static function formatString($format, $values) {
    foreach (array_keys($values) as $index => $key) {
      $orig = "%(" . $key . ")";
      $new = "%" . ($index + 1) . "\$";
      $format = str_replace($orig, $new, $format);
    }

    if (preg_match("/%\\(\\w+\\)[bcdeEufFgGosxX]/", $format, $match))
      throw new Exception("formatString: Placeholder " . $match[0] . " not found");

    return vsprintf($format, array_values($values));
  }

  public static function isAbsoluteUrl($url) {
    return substr($url, 0, 7) == "http://" || substr($url, 0, 8) == "https://" || substr($url, 0, 2) == "//";
  }

  public static function getAbsoluteUrl($uri) {
    $protocol = (@$_SERVER["HTTPS"] && $_SERVER["HTTPS"] != "off") ? "https://" : "http://";
    $host = $_SERVER["HTTP_HOST"];
    return $protocol . $host . "/" . ltrim($uri, "/");
  }

  public static function redirect($page = '') {
    if (self::isAbsoluteUrl($page))
      header("Location: " . $page);
    else {
      $url      = APPLICATION_DOCROOT . ($page ? sprintf('/%s', $page) : '');
      $location = Util::getAbsoluteUrl($url);
      header("Location: " . $location);
    }

    die();
  }

  public static function redirectToLogin() {
    self::redirect('login');
  }

  public static function redirectToIndex() {
    self::redirect('index');
  }

  public static function getActiveSeason() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT sea_id, sea_description, sea_start_date " .
      "FROM tblseason " .
      "WHERE sea_active = 1";

    $statement = $dbHandler->prepare($query);
    $statement->execute();

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  public static function getPreviousSeason($date) {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT sea_id, sea_description, sea_start_date " .
      "FROM tblseason " .
      "WHERE sea_active = 1 " .
      "AND sea_start_date < ? " .
      "ORDER BY sea_start_date DESC " .
      "LIMIT 1";
    $params = array($date);

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  public static function getNextSeason($date) {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT sea_id, sea_description, sea_start_date " .
      "FROM tblseason " .
      "WHERE sea_active = 1 " .
      "AND sea_start_date > ? " .
      "ORDER BY sea_start_date " .
      "LIMIT 1";
    $params = array($date);

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }
}