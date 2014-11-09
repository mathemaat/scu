<?php

class Handler_Extern extends Handler_MenuItem
{
  public function __construct($arguments) {
    self::$token = 'EXTERNAL';

    parent::__construct($arguments);
  }
}