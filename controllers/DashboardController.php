<?php

namespace app\controllers;

use app\core\Application;
use app\core\Request;
use app\models\User;
use app\models\QnaPost;
use app\models\Tag;
use app\models\QnaPostTag;
use app\models\QnaHierarchy;

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

    // Handle component actions (POST requests from components)
    public function handleComponentAction(Request $request)
    {
        $action = $request->getBody()['action'] ?? '';
        
        // Based on the action, call the appropriate handler
        switch ($action) {
            case 'ask_question':
                return $this->handleAskQuestion($request);
            default:
                // Unknown action, redirect to dashboard
                $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType();
                header('Location: ' . $redirectPath);
                exit;
        }
    }
    
    // Handle the ask question action for QA forum
    protected function handleAskQuestion(Request $request)
    {
        // Get unsanitized data directly from $_POST for database storage
        $title = $_POST['title'] ?? '';
        $body = $_POST['body'] ?? '';
        
        $tagsJson = $_POST['tags'] ?? '[]';
        $tagNames = json_decode($tagsJson, true);
        if (!is_array($tagNames)) {
            $tagNames = [];
        }
        
        // Use prepared statements in your model to prevent SQL injection
        // rather than using htmlspecialchars() for database storage
        $qnaModel = new QnaPost();
        $postId = $qnaModel->createQuestion($_SESSION['user_id'], $title, $body, $tagNames);
        
        // Redirect back to the QA forum
        $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
        
        if ($postId) {
            header('Location: ' . $redirectPath . '?success=question_posted');
        } else {
            header('Location: ' . $redirectPath . '?error=post_failed');
        }
        exit;
    }
    
    // Get the dashboard type based on role
    protected function getDashboardType()
    {
        switch ($this->requiredRole) {
            case 'role-applicant':
                return 'applicant';
            case 'role-undergrad':
                return 'undergraduate';
            case 'role-profile':
                return 'profile';
            default:
                return 'applicant';
        }
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