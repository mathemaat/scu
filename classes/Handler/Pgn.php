<?php

class Handler_Pgn extends Handler_Resource
{
  public function search() {
    $player  = array_key_exists('player',  $_GET) ? $_GET['player']  : null;
    $page    = array_key_exists('page',    $_GET) ? $_GET['page']    : 1;

    $limit  = ITEMS_PER_PAGE;
    $offset = ITEMS_PER_PAGE * ($page - 1);

    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT pgn_id, pgn_date, pgn_white, pgn_black, res_description " .
      "FROM tblpgn " .
      "LEFT JOIN tblresult ON res_id = pgn_res_id " .
      "WHERE TRUE ";
    $params = array();

    if (strlen($player) >= 1)
    {
      $param = '%' . $player . '%';
      $query .= "AND (pgn_white LIKE ? OR pgn_black LIKE ?) ";
      $params = array_merge($params, array($param, $param));
    }

    $query .=
      "LIMIT " . $limit . " " .
      "OFFSET " . $offset;

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    $items = $statement->fetchAll(PDO::FETCH_ASSOC);

    if (count($items) >= 1) {
      $result = '<table>';
      $result .= '<tr>' . sprintf(str_repeat('<th>%s</th>', 6), '#', 'Datum', 'Wit', 'Zwart', 'Uitslag', 'Bekijken') . '</tr>';

      foreach($items as $item) {
        $datum = date('d-m-Y', strtotime($item['pgn_date']));
        $url = sprintf('<a href="%s/pgn/view/%d">Bekijken</a>', APPLICATION_DOCROOT, $item['pgn_id']);

        $result .= '<tr>' . sprintf(str_repeat('<td>%s</td>', 6), $item['pgn_id'], $datum, $item['pgn_white'], $item['pgn_black'], $item['res_description'], $url) . '</tr>';
      }

      $result .= '</table>';
    }
    else
      $result = 'Geen resultaten';

    $variables = array(
      'docroot' => APPLICATION_DOCROOT,
      'player'  => $player,
      'result'  => $result
    );

    $template = Util::getTemplate('pgn-search');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }

  public function upload() {
    if (array_key_exists('save', $_POST) && array_key_exists('contents', $_POST)) {
      $contents = trim($_POST['contents']);

      if (strlen($contents) >= 1) {
        $dbHandler = Application::getInstance()->getDBHandler();

        $pgnParams = self::parsePgnParams($contents);
        
        if (strlen($pgnParams['date']) >= 1 && strlen($pgnParams['white']) >= 1 && strlen($pgnParams['black']) >= 1)
        {
          $query = "INSERT INTO tblpgn (pgn_contents, pgn_date, pgn_white, pgn_black, pgn_res_id) VALUES (?, ?, ?, ?, ?)";
          $params = array($contents, $pgnParams['date'], $pgnParams['white'], $pgnParams['black'], $pgnParams['result']);
          
          $statement = $dbHandler->prepare($query);
          $statement->execute($params);
          
          Util::redirect(sprintf('pgn/view/%d', $dbHandler->lastInsertId()));
        }
        else
          throw new Exception('Invalid PGN-file');
      }
    }
    else if (array_key_exists('cancel', $_POST))
      Util::redirect('pgn/search');

    $variables['docroot']  = APPLICATION_DOCROOT;

    $template = Util::getTemplate('pgn-upload');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }

  protected static function parsePgnParams($pgn)
  {
    $patterns = array(
      'date'   => '/\[date "[\d]{4}.[\d]{2}.[\d]{2}"\]/i',
      'white'  => '/\[white "[\w ,-]+"\]/i',
      'black'  => '/\[black "[\w ,-]+"\]/i',
      'result' => '/\[result "[\d-\/]+"\]/i'
    );

    foreach($patterns as $field => $pattern)
    {
      $pgnParams[$field] = null;

      if (!preg_match($pattern, $pgn, $matches)) continue;

      $match = $matches[0];

      $start = strpos($match, '"');
      $end   = strrpos($match, '"');

      if ($end - $start <= 1) continue;

      $parsedValue = substr($match, $start + 1, $end - $start - 1);

      switch($field)
      {
        case 'date':
        {
          $pgnParam = str_replace('.', '-', $parsedValue);
          break;
        }
        case 'white':
        case 'black':
        {
          $parts = explode(',', $parsedValue);
          $pgnParam = trim($parts[1] . ' ' . $parts[0]);
          break;
        }
        case 'result':
        {
          switch($parsedValue)
          {
            // WARNING: hardcoded IDs
            case '1-0':     $pgnParam = 1;    break;
            case '0-1':     $pgnParam = 2;    break;
            case '1/2-1/2': $pgnParam = 3;    break;
            default:        $pgnParam = null; break;
          }
          break;
        }
        default:
        {
          $pgnParam = null;
          break;
        }
      }

      $pgnParams[$field] = $pgnParam;
    }

    return $pgnParams;
  }

  public function view()
  {
    if (count($this->arguments) == 0)
      Router::notFound();

    $pgnId = array_shift($this->arguments);
    $pgnFile = sprintf('%s/static/pgn/%04d.pgn', APPLICATION_PATH, $pgnId);

    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      "SELECT pgn_id, pgn_white, pgn_black, pgn_date, pgn_contents, res_id, res_description " .
      "FROM tblpgn " .
      "LEFT JOIN tblresult ON res_id = pgn_res_id " .
      "WHERE pgn_id = ?";
    $params = array($pgnId);

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);
    $pgn = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$pgn)
      Router::notFound();

    if (!file_exists($pgnFile))
    {
      $success = file_put_contents($pgnFile, $pgn['pgn_contents']);
      if (!$success)
        throw new Exception('Unable to view PGN');
  }

    $variables = array(
      'docroot' => APPLICATION_DOCROOT,
      'white'   => $pgn['pgn_white'],
      'black'   => $pgn['pgn_black'],
      'date'    => date('d-m-Y', strtotime($pgn['pgn_date'])),
      'result'  => !is_null($pgn['res_id']) ? $pgn['res_description'] : 'Onbekend',
      'pgn-id'  => sprintf('%04d', $pgnId)
    );

    $template = Util::getTemplate('pgn-view');

    echo $this->formatTemplate(array('body' => Util::formatString($template, $variables)));
  }
}
