<?php

namespace app\controllers;

/**
 * Controller for the profile/admin dashboard
 */
class ProfileDashController extends DashboardController
{
    protected $validComponents = [
        'qa-forum',
        'publish-events',
        'announcements'
    ];
    
    protected $dashboardTemplate = 'dashboard_pro.php';
    protected $defaultComponent = 'qa-forum';
}