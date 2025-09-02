<?php

require_once dirname(__DIR__) . '/core/Application.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/core/Request.php';

// Require controller files
require_once dirname(__DIR__) . '/controllers/DashboardController.php';
require_once dirname(__DIR__) . '/controllers/ApplicantDashController.php';
require_once dirname(__DIR__) . '/controllers/UndergradDashController.php';
require_once dirname(__DIR__) . '/controllers/ProfileDashController.php';

use app\core\Application;

$app = new Application(dirname(__DIR__));

require_once dirname(__DIR__) . '/core/Routes.php';

$app->run();

?>