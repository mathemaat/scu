<?php

class Util
{
  public static $dagen   = array(1 => 'Ma', 'Di', 'Wo', 'Do', 'Vr', 'Za', 'Zo');
  public static $maanden = array(1 => 'Jan', 'Feb', 'Mrt', 'Apr', 'Mei', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dec');

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

  public static function shortenPost($text) {
    $length = strlen($text);

    if ($length <= POST_LIST_POST_LENGTH)
      return $text;

    return trim(substr($text, 0, POST_LIST_POST_LENGTH - 4)) . ' ...';
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

  public static function preparePost($post) {
    $count = preg_match_all('/\[pgn-\d{1,}\]/i', $post, $matches);

    if ($count === false || $count == 0)
      return $post;

    foreach($matches[0] as $match) {
      $pgnId   = substr($match, 5, strlen($match) - 6);
      $pgnFile = sprintf('%s/static/pgn/%04d.pgn', APPLICATION_PATH, $pgnId);

      if (!file_exists($pgnFile)) {
        $dbHandler = Application::getInstance()->getDBHandler();

        $query = "SELECT pgn_contents FROM tblpgn WHERE pgn_id = ?";
        $params = array($pgnId);

        $statement = $dbHandler->prepare($query);
        $statement->execute($params);
        $contents = $statement->fetch(PDO::FETCH_COLUMN);

        $success = file_put_contents($pgnFile, $contents);
        if (!$success)
          continue;
      }

      $viewer = sprintf(
        '<div id="pgn%1$d-container"></div>' .
        '<div id="pgn%1$d-moves"></div>' .
        '<script>new PgnViewer({ boardName: "pgn%1$d", pgnFile: "%2$s/static/pgn/%1$04d.pgn", pieceSet: "case"});</script>',
        $pgnId, APPLICATION_DOCROOT
      );

      $post = str_replace($match, $viewer, $post);
    }

    return $post;
  }
}