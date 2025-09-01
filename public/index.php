<?php

require_once dirname(__DIR__) . '/core/Application.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/core/Request.php';

use app\core\Application;

$app = new Application(dirname(__DIR__));

require_once dirname(__DIR__) . '/core/Routes.php';

$app->run();

?>