<?php

class Handler_MenuItem extends Handler
{
  // de gegevens van het geselecteerde menuitem
  protected static $properties;

  // de tabbladen van het geselecteerde menuitem
  protected static $tabs;

  // het standaard tabblad van het geselecteerde menuitem
  protected static $defaultTab;

  // de gegevens van het geselecteerde menuitem
  protected static $selectedTab;

  // de artikelen van het geselecteerde menuitem en tabblad
  protected static $posts;

  // de gevraagde informatie van het geselecteerde menuitem, waaronder tabblad en seizoen
  protected static $GETParams;

  public function __construct($arguments) {
    parent::__construct($arguments);
    
    self::loadProperties($arguments[0]);
    
    self::$tabs        = self::getTabs();
    self::$defaultTab  = self::getDefaultTab();
    
    self::setGETParams();
    
    self::$selectedTab = self::getSelectedTab();
    self::$posts       = self::getPosts();
  }

  protected static function loadProperties($token) {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT mni_id, mni_description, mni_token, mni_has_tabs ' .
      'FROM tblmenuitem ' .
      'WHERE mni_token = ?';
    $params = array($token);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    self::$properties  = $statement->fetch(PDO::FETCH_ASSOC);
  }

  // leest de $_GET parameters uit om uit te zoeken welke informatie de gebruiker opvraagt
  protected function setGETParams() {
    $defaultParams = array(
      'season' => null,
      'tab'    => ''
    );

    self::$GETParams = $defaultParams;

    if (!isset($_GET)) return;

    if ($this->selectedSeason['sea_id'] != $this->activeSeason['sea_id'])
      self::$GETParams['season'] = $this->selectedSeason['sea_id'];

    if (array_key_exists('tab', $_GET) && in_array($_GET['tab'], array_keys(self::$tabs)))
      self::$GETParams['tab'] = $_GET['tab'];
  }

  protected static function getTabs() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT tab_description, tab_token ' .
      'FROM tbltab ' .
      'WHERE tab_mni_id = ? ' .
      'ORDER BY tab_sort_order';
    $params = array(self::$properties['mni_id']);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    $tabs = $statement->fetchAll(PDO::FETCH_ASSOC);

    $array = array();
    foreach ($tabs as $tab)
      $array[$tab['tab_token']] = $tab['tab_description'];

    return $array;
  }

  protected static function getDefaultTab() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT tab_id, tab_description, tab_token ' .
      'FROM tbltab ' .
      'WHERE tab_mni_id = ? ' .
      'AND tab_default = 1';
    $params = array(self::$properties['mni_id']);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }
  
  protected static function getSelectedTab() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT tab_id, tab_description, tab_token ' .
      'FROM tbltab ' .
      'WHERE tab_token = ?';
    $params = array(self::$GETParams['tab']);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }
  
  protected static function getPosts() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT pst_id, pst_date, pst_title, pst_contents ' .
      'FROM tblpost ' .
      'WHERE pst_mni_id = ? ';
    $params = array(self::$properties['mni_id']);
    
    if (self::$properties['mni_has_tabs']) {
      $query .= 'AND pst_tab_id = ?';
      $params[] = self::$selectedTab['tab_id'];
    }
    
    if (self::$GETParams['season']) {
      $query .= 'AND pst_sea_id = ?';
      $params[] = self::$GETParams['season'];
    }
    
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }
  
  public function handleRequest() {
    if (self::$properties['mni_has_tabs'])
      $template = Util::getTemplate('post-history');
    else
      $template = Util::getTemplate('post-history-no-tabs');

    $variables = array(
      'tabs'  => $this->getTabsHtml(),
      'title' => self::$properties['mni_has_tabs'] ? self::$selectedTab['tab_description'] : self::$properties['mni_description'],
      'posts' => $this->getPostsHtml()
    );

    $body = $this->getSeasonWindow() . Util::formatString($template, $variables);

    echo $this->formatTemplate(array('body' => $body));
  }
  
  public function getTabsHtml() {
    $items = array();
    foreach(self::$tabs as $token => $description) {
      if ($token == self::$GETParams['tab'] || ($token == self::$defaultTab['tab_token'] && !self::$GETParams['tab']))
        $class = 'class="active"';
      else
        $class = '';

      $params = self::$GETParams;
      $params['tab'] = $token;

      $GETParamsLinkPart = $this->getGETParamsLinkPart($params);

      $items[] = sprintf('<li><a href="%s/%s%s" %s>%s</a></li>', APPLICATION_DOCROOT, self::$properties['mni_token'], $GETParamsLinkPart, $class, $description);
    }

    return '<ul>' . implode('', $items) . '</ul>';
  }
  
  protected function getPostsHtml() {
    if (count(self::$posts) == 0)
      return 'Er zijn nog geen artikelen geschreven voor deze pagina.';
    
    $template = Util::getTemplate('post-cell');
    
    $items = array();
    foreach(self::$posts as $post) {
      $variables = array(
        'post-id'       => $post['pst_id'],
        'title'         => $post['pst_title'],
        'creation-info' => date('d-m-Y', strtotime($post['pst_date'])),
        'contents'      => substr($post['pst_contents'], 0, 200)
      );
      
      $items[] = Util::formatString($template, $variables);
    }
    
    return implode('', $items);
  }
  
  // create a link part containing the $_GET parameters based on $params
  protected function getGETParamsLinkPart($params) {
    $parts = array();

    if ($params['season'] > 0)
      $parts[] = 'season=' . htmlspecialchars($params['season']);

    if (strlen($params['tab']) >= 1 && in_array($params['tab'], array_keys(self::$tabs)))
      $parts[] = 'tab=' . htmlspecialchars($params['tab']);

    if (count($parts) >= 1)
      return '?' . implode('&', $parts);

    return '';
  }
}