<?php

class Handler_Nieuws extends Handler
{
  public function handleRequest() {
    echo $this->formatTemplate(array('body' => 'Nieuws!'));
  }
}