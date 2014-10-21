<?php

class Handler_Index extends Handler
{
  public function handleRequest() {
    echo $this->formatTemplate(array('body' => 'Home!'));
  }
}