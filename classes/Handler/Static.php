<?php

class Handler_Static extends Handler
{
  const PAGEDIR = 'static/pages';

  private $templateVars = null;

  public function handleRequest() {
    $template = self::PAGEDIR . '/' . $this->arguments[0] . '.html';
    $html     = file_get_contents($template);

    if (count($this->templateVars) >= 1)
      $html = Util::formatString($html, $this->templateVars);

    echo $this->formatTemplate(array('body' => $html));
  }

  public function setTemplateVars($variables) {
    $this->templateVars = $variables;
  }
}