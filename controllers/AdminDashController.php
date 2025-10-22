<?php

namespace app\controllers;

/**
 * Controller for the admin dashboard
 */
class AdminDashController extends DashboardController
{
    protected $validComponents = [
        'role-applications',
        'degree-programs-management',
        'content-review-queue'
    ];
    
    protected $dashboardTemplate = 'dashboard_adm.php';
    protected $defaultComponent = 'content-review-queue';
    protected $requiredRole = 'role-admin';
}