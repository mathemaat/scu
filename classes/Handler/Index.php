<?php

class Handler_Index extends Handler
{
  public function handleRequest() {
    $variables['class']    = 'post-list';
    $variables['agenda']   = $this->getAgenda();
    $variables['contents'] = $this->getPostsHtml();

    $template = Util::getTemplate('home');

    $body = $this->getSeasonWindow() . Util::formatString($template, $variables);

    echo $this->formatTemplate(array('body' => $body));
  }

  protected static function getPosts() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT pst_id, pst_date, pst_title, pst_contents, tab_id, tab_description ' .
      'FROM tblpost ' .
      'INNER JOIN tbltab ON tab_id = pst_tab_id ' .
      'ORDER BY pst_date DESC ' .
      'LIMIT 10';
    $params = array();

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  protected function getPostsHtml() {
    $posts = self::getPosts();
    if (count($posts) == 0)
      return 'Er zijn nog geen artikelen geschreven voor deze pagina.';

    $template = Util::getTemplate('post-cell');

    $items = array();
    foreach($posts as $post) {
      $variables = array(
        'docroot'   => APPLICATION_DOCROOT,
        'post-id'   => $post['pst_id'],
        'title'     => $post['tab_description'] . ' - ' . $post['pst_title'],
        'meta-data' => date('d-m-Y', strtotime($post['pst_date'])),
        'contents'  => Util::shortenPost($post['pst_contents'])
      );

      $items[] = Util::formatString($template, $variables);
    }

    return implode('', $items);
  }

  protected function getAgenda() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $now    = date('Y-m-d');
    $future = date('Y-m-d', strtotime('+4 weeks'));

    $query =
      'SELECT agi_id, agi_datum, agi_tijd, agi_omschrijving ' .
      'FROM tblagendaitem ' .
      'WHERE agi_datum BETWEEN ? AND ? ' .
      'ORDER BY agi_datum';
    $params = array($now, $future);

    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    $qrAgendaItems = $statement->fetchAll(PDO::FETCH_ASSOC);

    $rows = array();

    $rows[] =
      '<tr>' .
        '<th>Dag</th>' .
        '<th>Datum</th>' .
        '<th>Tijd</th>' .
        '<th>Omschrijving</th>' .
      '</tr>';

    foreach($qrAgendaItems as $qrAgendaItem) {
      $rows[] = sprintf(
        '<tr>' .
          '<td>%s</td>' .
          '<td>%d %s</td>' .
          '<td>%s</td>' .
          '<td>%s</td>' .
        '</tr>',
        Util::$dagen[date('N', strtotime($qrAgendaItem['agi_datum']))],
        date('j', strtotime($qrAgendaItem['agi_datum'])),
        strtolower(Util::$maanden[date('n', strtotime($qrAgendaItem['agi_datum']))]),
        date('H:i', strtotime($qrAgendaItem['agi_tijd'])),
        $qrAgendaItem['agi_omschrijving']
      );
    }

    return '<table>' . "\n" . implode("\n", $rows) . "\n" . '</table>' . "\n";
  }
}