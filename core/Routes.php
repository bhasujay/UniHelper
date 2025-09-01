<?php

// Base routes
$app->router->get('/', 'home.html');
$app->router->get('/home', 'home.html');
$app->router->get('/login', 'login.html');
$app->router->get('/register', 'register.html');

// Dashboard base route
$app->router->get('/applicant', [app\controllers\DashController::class, 'index']);
$app->router->get('/dashboard/applicant/:component', [app\controllers\ApplicantDashController::class, 'renderComponent']);

// Other role-based dashboards
$app->router->get('/undergrad', 'dashboard_und.php');
$app->router->get('/profile', 'dashboard_pro.php');
$app->router->get('/moderator', 'dashboard_mod.php');