<?php

abstract class Handler_Resource extends Handler
{
  public function handleRequest() {
    if (!Router::allowUpload())
      Router::notFound();

    if (count($this->arguments) == 0)
      Router::notFound();

    $function = array_shift($this->arguments);
    if (in_array($function, array('search', 'view', 'upload')))
      $this->$function();
    else
      Router::notFound();
  }

  abstract function search();
  abstract function view();
  abstract function upload();
}
