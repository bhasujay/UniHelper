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

// Dashboard POST routes for component actions
$app->router->post('/dashboard/applicant/qa-forum/post', [app\controllers\ApplicantDashController::class, 'handleComponentAction']);
$app->router->post('/dashboard/undergraduate/qa-forum/post', [app\controllers\UndergradDashController::class, 'handleComponentAction']);
$app->router->post('/dashboard/profile/qa-forum/post', [app\controllers\ProfileDashController::class, 'handleComponentAction']);

// Dashboard GET routes for fetching question data
$app->router->get('/dashboard/applicant/qa-forum/question/:id', [app\controllers\ApplicantDashController::class, 'getQuestionData']);
$app->router->get('/dashboard/undergraduate/qa-forum/question/:id', [app\controllers\UndergradDashController::class, 'getQuestionData']);
$app->router->get('/dashboard/profile/qa-forum/question/:id', [app\controllers\ProfileDashController::class, 'getQuestionData']);

// Dashboard GET routes for deleting questions
$app->router->get('/dashboard/applicant/qa-forum/delete/:id', [app\controllers\ApplicantDashController::class, 'deleteQuestion']);
$app->router->get('/dashboard/undergraduate/qa-forum/delete/:id', [app\controllers\UndergradDashController::class, 'deleteQuestion']);
$app->router->get('/dashboard/profile/qa-forum/delete/:id', [app\controllers\ProfileDashController::class, 'deleteQuestion']);

// Add these routes for profile components
$app->router->get('/profile', [app\controllers\ProfileController::class, 'index']);
$app->router->get('/profile/edit', [app\controllers\ProfileController::class, 'edit']);
$app->router->post('/profile/update', [app\controllers\ProfileController::class, 'update']);

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
