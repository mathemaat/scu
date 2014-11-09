<?php

abstract class Handler_MenuItem extends Handler
{
  // de token waarmee het betreffende menu bekend is in de database
  protected static $token;

  // de gegevens van het geselecteerde menu item
  protected static $properties;

  public function __construct($arguments) {
    parent::__construct($arguments);

    self::loadProperties();
  }

  protected static function loadProperties() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT mni_id, mni_description, mni_token, mni_has_tabs ' .
      'FROM tblmenuitem ' .
      'WHERE mni_token = ? ' .
      'ORDER BY mni_sort_order';
    $params = array(self::$token);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    self::$properties = $statement->fetch(PDO::FETCH_ASSOC);
  }
  
  public function handleRequest() {
    if (self::$properties['mni_has_tabs'])
      $template = Util::getTemplate('post-history');
    else
      $template = Util::getTemplate('post-history-no-tabs');
    
    $variables = array(
      'tabs'  => $this->getTabsHtml(),
      'title' => self::$properties['mni_description'],
      'posts' => 'Posts here'
    );
    
    $body = $this->getSeasonWindow() . Util::formatString($template, $variables);
    
    echo $this->formatTemplate(array('body' => $body));
  }

  protected static function getTabs() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT tab_id, tab_description, tab_token ' .
      'FROM tbltab ' .
      'WHERE tab_mni_id = ? ' .
      'ORDER BY tab_sort_order';
    $params = array(self::$properties['mni_id']);
    $statement = $dbHandler->prepare($query);
    $statement->execute($params);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  public function getTabsHtml() {
    $tabs = self::getTabs();
    
    $parts = array();
    foreach($tabs as $tab)
      $parts[] .= sprintf('<li>%s</li>', $tab['tab_description']);
    
    return '<ul>' . implode('', $parts) . '</ul>';
  }
}