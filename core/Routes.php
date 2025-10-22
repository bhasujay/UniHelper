<?php

// Base routes
$app->router->get('/', 'home.html');
$app->router->get('/home', 'home.html');
$app->router->get('/login', 'login.php');
$app->router->get('/register', [app\controllers\AuthController::class, 'populateRegisterForm']);
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

// Z-Score API routes
$app->router->post('/api/z-score/save', [app\controllers\ApplicantDashController::class, 'saveZScore']);
$app->router->post('/api/z-score/update', [app\controllers\ApplicantDashController::class, 'saveZScore']); // Use Post method for both create and update
$app->router->get('/api/z-score/get', [app\controllers\ApplicantDashController::class, 'getZScore']);
$app->router->delete('/api/z-score/delete', [app\controllers\ApplicantDashController::class, 'deleteZScore']);

// Program search API routes
$app->router->get('/api/programs/search', [app\controllers\ApplicantDashController::class, 'searchPrograms']);
$app->router->get('/api/programs/filters', [app\controllers\ApplicantDashController::class, 'getSearchFilters']);
$app->router->get('/api/programs/autocomplete', [app\controllers\ApplicantDashController::class, 'getAutocomplete']);
// Add these routes for profile components
$app->router->get('/profile', [app\controllers\ProfileController::class, 'index']);
$app->router->get('/profile/edit', [app\controllers\ProfileController::class, 'edit']);
$app->router->post('/profile/update', [app\controllers\ProfileController::class, 'update']);
