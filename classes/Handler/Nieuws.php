<?php

class Handler_Nieuws extends Handler_MenuItem
{
  public function __construct($arguments) {
    self::$token = 'NEWS';

    parent::__construct($arguments);
  }
}