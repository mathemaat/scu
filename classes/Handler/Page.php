<?php

abstract class Handler_Page extends Handler
{
  // de gegevens van het geselecteerde menuitem
  protected static $menuItem;

  // de tabbladen van het geselecteerde menuitem
  protected static $tabs;

  // het standaard tabblad van het geselecteerde menuitem
  protected static $defaultTab;

  // de gegevens van het geselecteerde menuitem
  protected static $selectedTab;

  // de gevraagde informatie van het geselecteerde menuitem, waaronder tabblad en seizoen
  protected static $GETParams;

  public function __construct($arguments) {
    parent::__construct($arguments);

    self::$menuItem    = $this->getMenuItem();
    self::$tabs        = self::getTabs();
    self::$defaultTab  = self::getDefaultTab();
    self::$GETParams   = $this->setGETParams();
    self::$selectedTab = self::getSelectedTab();
  }

  abstract function getMenuItem();

  protected static function getTabs() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT tab_description, tab_token ' .
      'FROM tbltab ' .
      'WHERE tab_mni_id = ? ' .
      'ORDER BY tab_sort_order';
    $params = array(self::$menuItem['mni_id']);
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
    $params = array(self::$menuItem['mni_id']);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetch(PDO::FETCH_ASSOC);
  }

  abstract function setGETParams();

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

      $items[] = sprintf('<li><a href="%s/%s%s" %s>%s</a></li>', APPLICATION_DOCROOT, self::$menuItem['mni_token'], $GETParamsLinkPart, $class, $description);
    }

    return '<ul>' . implode('', $items) . '</ul>';
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