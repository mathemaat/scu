<?php

abstract class Handler
{
  protected $arguments;

  protected $activeSeason;
  protected $selectedSeason;

  public function __construct($arguments) {
    $this->arguments = $arguments;

    $activeSeason = Util::getActiveSeason();

    $this->activeSeason   = $activeSeason;
    $this->selectedSeason = $activeSeason;
  }

  public abstract function handleRequest();

  public function underConstruction() {
    echo $this->formatTemplate(array('body' => I18N::translate('Under_construction')));
    die();
  }

  public function formatTemplate($variables, $template = "outline") {
    $html = Util::getTemplate($template);

    /* merge template variables with default template variables */
    if ($template == 'outline')
      $variables = array_merge($this->getTemplateVariablesOutline(), $variables);

    return Util::formatString($html, $variables);
  }

  public function getTemplateVariablesOutline() {
    return array(
      'docroot' => APPLICATION_DOCROOT,
      'title'   => APPLICATION_TITLE,
      'menu'    => self::getMenu(),
      'year'    => date('Y')
    );
  }

  protected static function getMenuItems() {
    $dbHandler = Application::getInstance()->getDBHandler();

    $query =
      'SELECT mni_description, mni_token, tab_token ' .
      'FROM tblmenuitem ' .
      'LEFT JOIN tbltab ON tab_id = (' .
        'SELECT tab_id ' .
        'FROM tbltab ' .
        'WHERE tab_mni_id = mni_id ' .
        'AND tab_default = 1 ' .
        'LIMIT 1' .
      ') ' .
      'ORDER BY mni_sort_order';
    $statement = $dbHandler->prepare($query);
    $statement->execute();

    return $statement->fetchAll(PDO::FETCH_ASSOC);
  }

  protected static function getMenu() {
    $uri = Router::getRequestUri();

    $items = array();

    // voeg de home knop toe aan het menu
    if ($uri == '' || $uri == 'index')
      $class = "class='active'";
    else
      $class = '';

    $items[] = sprintf("<li><a href='%s/index' %s>HOME</a></li>", APPLICATION_DOCROOT, $class);

    // voeg de overige menuitems toe aan het menu
    $menuItems = self::getMenuItems();
    foreach ($menuItems as $menuItem) {
      $class = $menuItem['mni_token'] == $uri ? "class='active'" : "";

      $url = sprintf('%s/%s?tab=%s', APPLICATION_DOCROOT, $menuItem['mni_token'], $menuItem['tab_token']);

      $items[] = sprintf(
        "<li><a href='%s' %s>%s</a></li>", $url, $class, strtoupper($menuItem['mni_description'])
      );
    }

    return sprintf("<ul>%s</ul>", implode('', $items));
  }

  public function getSeasonWindow() {
    $format = Util::getTemplate('season_window');

    $previousSeason = Util::getPreviousSeason($this->selectedSeason['sea_start_date']);
    $nextSeason     = Util::getNextSeason($this->selectedSeason['sea_start_date']);

    if ($previousSeason) {
      $previousSeasonHTML = sprintf(
        "<a href='#' title='Ga naar seizoen %s'>◄</a>",
        $previousSeason['sea_description']
      );
    }
    else
      $previousSeasonHTML = '&nbsp;';

    if ($nextSeason) {
      $nextSeasonHTML = sprintf(
        "<a href='#' title='Ga naar seizoen %s'>►</a>",
        $nextSeason['sea_description']
      );
    }
    else
      $nextSeasonHTML = '&nbsp;';

    $variables = array(
      'title'      => $this->selectedSeason['sea_description'],
      'previous'   => $previousSeasonHTML,
      'next'       => $nextSeasonHTML,
    );

    return Util::formatString($format, $variables);
  }
}