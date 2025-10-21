<?php

namespace app\controllers;

use app\core\Application;
use app\models\User;

abstract class DashboardController
{
    protected $validComponents = [];
    protected $dashboardTemplate = '';
    protected $defaultComponent = '';
    protected $requiredRole = '';
    protected $user; // Add user property
    protected $activeComponent = ''; // Add active component tracker

    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /UniHelper/login');
            exit;
        }

        // Get the user from database
        $userModel = new User();
        $this->user = $userModel->findById($_SESSION['user_id']);
        
        if (!$this->user) {
            session_destroy();
            header('Location: /UniHelper/login');
            exit;
        }

        // Check role-based access if required
        if (!empty($this->requiredRole) && $this->user->role !== $this->requiredRole) {
            header('Location: /UniHelper/dashboard');
            exit;
        }
    }

    // Default entry point for dashboard
    public function index()
    {
        // Set active component
        $this->activeComponent = $this->defaultComponent;
        
        // Load default component
        $content = $this->loadComponent($this->defaultComponent . '.php');
        return $this->renderDashboard($content);
    }

    // Renders a specific component based on URL parameter
    public function renderComponent($params)
    {
        $component = $params['component'] ?? $this->defaultComponent;
        
        // Security check - only allow whitelisted components
        if (!in_array($component, $this->validComponents)) {
            return $this->renderDashboard("<div class='error'>Component not found or not authorized</div>");
        }
        
        // Set active component
        $this->activeComponent = $component;
        
        // Load the requested component
        $content = $this->loadComponent($component . '.php');
        return $this->renderDashboard($content);
    }

    // Loads a component file
    protected function loadComponent($componentName)
    {
        $componentPath = Application::$ROOT_DIR . "/views/components/{$componentName}";
        
        if (!file_exists($componentPath)) {
            return "<div class='error'>Component {$componentName} not found</div>";
        }
        
        // Make user available to component
        $user = $this->user;
        
        // Capture the component content
        ob_start();
        include $componentPath;
        return ob_get_clean();
    }

    // Renders the dashboard with the given content
    protected function renderDashboard($content = null)
    {
        // Make user available to the dashboard template
        $user = $this->user;
        
        // Make active component available to the dashboard template
        $activeComponent = $this->activeComponent;
        
        // Make content available to the dashboard template
        include Application::$ROOT_DIR . "/views/{$this->dashboardTemplate}";
    }
}