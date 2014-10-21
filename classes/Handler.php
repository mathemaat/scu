<?php

abstract class Handler
{
  protected $arguments;

  public function __construct($arguments) {
    $this->arguments = $arguments;
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
      'nieuws' => 'Nieuws'
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
}