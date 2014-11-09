<?php

class Handler_Intern extends Handler_MenuItem
{
  public function __construct($arguments) {
    self::$token = 'INTERNAL';

    parent::__construct($arguments);
  }
}