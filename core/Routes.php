<?php

// Base routes
$app->router->get('/home', 'home.html');

// Authentication routes
$app->router->get('/register', [app\controllers\AuthController::class, 'populateRegisterForm']);
$app->router->post('/register', [app\controllers\AuthController::class, 'register']);
$app->router->get('/login', 'login.php');
$app->router->post('/login', [app\controllers\AuthController::class, 'login']);
$app->router->get('/logout', [app\controllers\AuthController::class, 'logout']);

// OTP routes
$app->router->get('/otp/generate', [app\controllers\OtpController::class, 'generateOtpAction']);
$app->router->post('/otp/validate', [app\controllers\OtpController::class, 'validateOtpAction']);

// Add these routes for profile components
$app->router->get('/profile/view', [app\controllers\DashboardController::class, 'profileIndex']);
$app->router->get('/profile/edit', [app\controllers\DashboardController::class, 'profileIndex']);
$app->router->post('/profile/update', [app\controllers\DashboardController::class, 'profileUpdate']);

// Dashboard base route
$app->router->get('/', [app\controllers\DashboardController::class, 'index']);
$app->router->get('/dashboard', [app\controllers\DashboardController::class, 'index']);
$app->router->get('/:component', [app\controllers\DashboardController::class, 'renderComponent']);

// Z-Score API routes
$app->router->post('/api/z-score/save', [app\controllers\ApplicantDashController::class, 'saveZScore']);
$app->router->post('/api/z-score/update', [app\controllers\ApplicantDashController::class, 'saveZScore']); // Use Post method for both create and update
$app->router->get('/api/z-score/get', [app\controllers\ApplicantDashController::class, 'getZScore']);
$app->router->delete('/api/z-score/delete', [app\controllers\ApplicantDashController::class, 'deleteZScore']);

// Program search API routes
$app->router->get('/api/programs/search', [app\controllers\ApplicantDashController::class, 'searchPrograms']);
$app->router->get('/api/programs/filters', [app\controllers\ApplicantDashController::class, 'getSearchFilters']);
$app->router->get('/api/programs/autocomplete', [app\controllers\ApplicantDashController::class, 'getAutocomplete']);

// Wishlist API routes
$app->router->get('/api/wishlist/check', [app\controllers\ApplicantDashController::class, 'checkWishlist']);
$app->router->post('/api/wishlist/add', [app\controllers\ApplicantDashController::class, 'addToWishlist']);
$app->router->delete('/api/wishlist/remove', [app\controllers\ApplicantDashController::class, 'removeFromWishlist']);
$app->router->get('/api/wishlist/count', [app\controllers\ApplicantDashController::class, 'getWishlistCount']);
$app->router->get('/api/wishlist/items', [app\controllers\ApplicantDashController::class, 'getWishlistItems']);

// Degree programs routes
$app->router->post('/dashboard/admin/degreemanage/add', [app\controllers\AdminDashController::class, 'addDegreeProgram']);
$app->router->get('/dashboard/admin/degreemanage/remove/:id', [app\controllers\AdminDashController::class, 'removeDegreeProgram']);
$app->router->post('/dashboard/admin/degreemanage/update/:id', [app\controllers\AdminDashController::class, 'updateDegreeProgramForm']);
$app->router->get('/dashboard/admin/degreemanage/get/:id', [app\controllers\AdminDashController::class, 'getDegreeProgramData']);
$app->router->get('/dashboard/admin/degreemanage/get/all', [app\controllers\AdminDashController::class, 'getDegreePrograms']);
