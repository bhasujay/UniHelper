<?php

namespace app\controllers;

/**
 * Controller for the university applicant dashboard
 */
class ApplicantDashController extends DashboardController
{
    protected $validComponents = [
        'z-score-checker',
        'degree-programs',
        'wishlist',
        'find-applicant',
        'unicode-generator',
        'qa-forum',
        'connect-undergrads'
    ];
    
    protected $dashboardTemplate = 'dashboard_app.php';
    protected $defaultComponent = 'qa-forum';
}