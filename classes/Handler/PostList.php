<?php

class Handler_PostList extends Handler_Page
{
  // de artikelen van het geselecteerde menuitem en tabblad
  protected static $posts;

  public function __construct($arguments) {
    parent::__construct($arguments);
  }

  public function getMenuItem() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT mni_id, mni_description, mni_token ' .
      'FROM tblmenuitem ' .
      'WHERE mni_token = ?';
    $params = array($this->arguments[0]);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  // leest de $_GET parameters uit om uit te zoeken welke informatie de gebruiker opvraagt
  public function setGETParams() {
    $GETParams = array(
      'season' => null,
      'tab'    => ''
    );

    if (!isset($_GET))
      return $GETParams;

    if ($this->selectedSeason['sea_id'] != $this->activeSeason['sea_id'])
      $GETParams['season'] = $this->selectedSeason['sea_id'];

    if (array_key_exists('tab', $_GET) && in_array($_GET['tab'], array_keys(self::$tabs)))
      $GETParams['tab'] = $_GET['tab'];

    return $GETParams;
  }

  public function handleRequest() {
    $variables['class'] = 'post-list';
    $variables['tabs']  = $this->getTabsHtml();
    $variables['title'] = self::$selectedTab['tab_description'];

    if (self::$selectedTab['tab_token'] == 'agenda')
      $variables['contents'] = $this->getAgenda();
    else
      $variables['contents'] = $this->getPostsHtml();

    $template = Util::getTemplate('page');

    $body = $this->getSeasonWindow() . Util::formatString($template, $variables);

    echo $this->formatTemplate(array('body' => $body));
  }

  protected static function getPosts() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT pst_id, pst_date, pst_title, pst_contents ' .
      'FROM tblpost ' .
      'WHERE pst_mni_id = ? ';
    $params = array(self::$menuItem['mni_id']);

    $query .= 'AND pst_tab_id = ? ';
    $params[] = self::$selectedTab['tab_id'];

    if (self::$GETParams['season']) {
      $query .= 'AND pst_sea_id = ? ';
      $params[] = self::$GETParams['season'];
    }

    $query .= 'ORDER BY pst_date DESC';

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
        'title'     => $post['pst_title'],
        'meta-data' => date('d-m-Y', strtotime($post['pst_date'])),
        'contents'  => Util::shortenPost($post['pst_contents'])
      );

      $items[] = Util::formatString($template, $variables);
    }

    return implode('', $items);
  }

  protected function getAgenda() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT agi_id, agi_datum, agi_tijd, agi_omschrijving ' .
      'FROM tblagendaitem ' .
      'WHERE agi_sea_id = ? ' .
      'ORDER BY agi_datum';
    $params = array($this->selectedSeason['sea_id']);

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