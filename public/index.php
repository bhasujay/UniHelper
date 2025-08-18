<?php

require_once __DIR__ . '/../core/Application.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../core/Request.php';

use app\core\Application;


$app = new Application(__DIR__);

$app->router->get('/','home');
$app->router->get('/home','home');
$app->router->get('/login','login');
$app->router->get('/register','register');

$app->run();

?>
