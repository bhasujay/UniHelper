<?php

namespace app\controllers;

use app\core\Application;

abstract class DashboardController
{
    protected $validComponents = [];
    protected $dashboardTemplate = '';
    protected $defaultComponent = '';

    // Default entry point for dashboard
    public function index()
    {
        // Load default component
        $content = $this->loadComponent($this->defaultComponent . '.html');
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
        
        // Load the requested component
        $content = $this->loadComponent($component . '.html');
        return $this->renderDashboard($content);
    }

    // Loads a component file
    protected function loadComponent($componentName)
    {
        $componentPath = Application::$ROOT_DIR . "/views/components/{$componentName}";
        
        if (!file_exists($componentPath)) {
            return "<div class='error'>Component {$componentName} not found</div>";
        }
        
        // Capture the component content
        ob_start();
        include $componentPath;
        return ob_get_clean();
    }

    // Renders the dashboard with the given content
    protected function renderDashboard($content = null)
    {
        // Make content available to the dashboard template
        include Application::$ROOT_DIR . "/views/{$this->dashboardTemplate}";
    }
}