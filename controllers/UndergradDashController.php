<?php

namespace app\controllers;

/**
 * Controller for the undergraduate dashboard
 */
class UndergradDashController extends DashboardController
{
    protected $validComponents = [
        'qa-forum',
        'create-session',
        'my-sessions',
        'join-sessions',
        'flagged-content',
        'banned-users'
    ];
    
    protected $dashboardTemplate = 'dashboard_und.php';
    protected $defaultComponent = 'qa-forum';
    protected $requiredRole = 'role-undergrad';
}