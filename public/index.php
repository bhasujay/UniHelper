<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// require core files
require_once dirname(__DIR__) . '/core/Application.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/core/Database.php';

// these require statements will be moved to an autoloader in the future

use app\core\Application;

$app = new Application(dirname(__DIR__));

$app->run();

?>