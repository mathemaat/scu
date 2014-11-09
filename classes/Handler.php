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
      'docroot'             => APPLICATION_DOCROOT,
      'title'               => APPLICATION_TITLE,
      'menu'                => self::getMenu(),
      'year'                => date('Y')
    );
  }

  protected static function getMenu() {
    $menuItems = array(
      'index'  => 'Home',
      'nieuws' => 'Nieuws',
      'intern' => 'Intern',
      'extern' => 'Extern',
    );

    $uri = Router::getRequestUri();

    $listItems = array();
    foreach ($menuItems as $key => $menuItem) {
      if ($key == 'home' && $uri == '')
        $class = "class='active'";
      else
        $class = $key == $uri ? "class='active'" : "";

      $url = sprintf("<a href='%s/%s' %s>%s</a>", APPLICATION_DOCROOT, $key, $class, strtoupper($menuItem));

      $listItems[] = sprintf("<li>%s</li>", $url);
    }

    return sprintf("<ul>%s</ul>", implode('', $listItems));
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