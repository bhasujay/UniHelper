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
require_once dirname(__DIR__) . '/core/mailer.php';

// these require statements will be moved to an autoloader in the future

// Require controller files
require_once dirname(__DIR__) . '/controllers/AuthController.php';
require_once dirname(__DIR__) . '/controllers/DashboardController.php';
require_once dirname(__DIR__) . '/controllers/ApplicantDashController.php';
require_once dirname(__DIR__) . '/controllers/UndergradDashController.php';
require_once dirname(__DIR__) . '/controllers/ProfileDashController.php';
require_once dirname(__DIR__) . '/controllers/AdminDashController.php';
require_once dirname(__DIR__) . '/controllers/common/otpController.php';

// Require model files
require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/User.php';
require_once dirname(__DIR__) . '/models/University.php';
require_once dirname(__DIR__) . '/models/Major.php';
require_once dirname(__DIR__) . '/models/QnaPost.php';
require_once dirname(__DIR__) . '/models/Tag.php';
require_once dirname(__DIR__) . '/models/QnaPostTag.php';
require_once dirname(__DIR__) . '/models/QnaHierarchy.php';
require_once dirname(__DIR__) . '/models/DegreeProgram.php';
require_once dirname(__DIR__) . '/models/ZScore_model.php';
require_once dirname(__DIR__) . '/models/ProgramModel.php';
require_once dirname(__DIR__) . '/models/WishlistModel.php';
require_once dirname(__DIR__) . '/models/otp.php';

use app\core\Application;

$app = new Application(dirname(__DIR__));

require_once dirname(__DIR__) . '/core/Routes.php';

$app->run();

?>