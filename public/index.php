<?php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

require_once dirname(__DIR__) . '/core/Application.php';
require_once dirname(__DIR__) . '/core/Router.php';
require_once dirname(__DIR__) . '/core/Request.php';
require_once dirname(__DIR__) . '/core/Database.php';

// Require controller files
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/DashboardController.php';
require_once dirname(__DIR__) . '/controllers/ApplicantDashController.php';
require_once dirname(__DIR__) . '/controllers/UndergradDashController.php';
require_once dirname(__DIR__) . '/controllers/ProfileDashController.php';
require_once dirname(__DIR__) . '/controllers/ProfileController.php';
require_once dirname(__DIR__) . '/controllers/AdminDashController.php';


// Require model files
require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/ZScore_model.php';
require_once dirname(__DIR__) . '/models/ProgramModel.php';
require_once dirname(__DIR__) . '/models/University.php';
require_once dirname(__DIR__) . '/models/Major.php';

use app\core\Application;

$app = new Application(dirname(__DIR__));

require_once dirname(__DIR__) . '/core/Routes.php';

$app->run();

?>