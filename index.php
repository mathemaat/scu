<?php

$path = __DIR__;

$scriptName = $_SERVER['SCRIPT_NAME'];
if ($scriptName == '/index.php')
  $docroot = '';
else
  $docroot = dirname($scriptName);

define('APPLICATION_PATH',    $path);
define('APPLICATION_DOCROOT', $docroot);

require_once 'settings.php';
require_once 'classes/Application.php';

Application::getInstance();
Router::route();