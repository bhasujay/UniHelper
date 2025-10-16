<?php

// Base routes
$app->router->get('/', 'home.html');
$app->router->get('/home', 'home.html');
$app->router->get('/login', 'login.html');
$app->router->get('/register', 'register.html');
$app->router->get('/logout', [app\controllers\AuthController::class, 'logout']);

// Authentication routes - POST (process forms)
$app->router->post('/login', [app\controllers\AuthController::class, 'login']);
$app->router->post('/register', [app\controllers\AuthController::class, 'register']);

// Dashboard base routes - default component
$app->router->get('/dashboard/applicant', [app\controllers\ApplicantDashController::class, 'index']);
$app->router->get('/dashboard/undergraduate', [app\controllers\UndergradDashController::class, 'index']);
$app->router->get('/dashboard/profile', [app\controllers\ProfileDashController::class, 'index']);
$app->router->get('/dashboard/admin', [app\controllers\AdminDashController::class, 'index']);

// Dashboard component routes - dynamic paths
$app->router->get('/dashboard/applicant/:component', [app\controllers\ApplicantDashController::class, 'renderComponent']);
$app->router->get('/dashboard/undergraduate/:component', [app\controllers\UndergradDashController::class, 'renderComponent']);
$app->router->get('/dashboard/profile/:component', [app\controllers\ProfileDashController::class, 'renderComponent']);
$app->router->get('/dashboard/admin/:component', [app\controllers\AdminDashController::class, 'renderComponent']);