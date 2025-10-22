<?php

namespace app\controllers;

use app\core\Application;
use app\core\Request;
use app\models\User;
use app\models\QnaPost;
use app\models\Tag;
use app\models\QnaPostTag;
use app\models\QnaHierarchy;
use app\models\Major;
use app\models\University;
use app\models\DegreeProgram;

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
            case 'update_question':
                return $this->handleUpdateQuestion($request);
            case 'delete_question':
                return $this->handleDeleteQuestion($request);
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
        $title = $request->getBody()['title'] ?? '';
        $body = $request->getBody()['body'] ?? '';
        $tagsJson = $request->getBody()['tags'] ?? '[]';
        
        // Basic validation
        if (empty($title) || empty($body)) {
            // Redirect back with error message
            $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=empty_fields');
            exit;
        }
        
        // Process the tags
        $tagsJson = $request->getBody()['tags'] ?? '[]';
        error_log("Raw tags JSON: " . $tagsJson);

        // Add HTML entity decoding before JSON parsing
        $tagsJson = html_entity_decode($tagsJson);
        error_log("After HTML decode: " . $tagsJson);

        try {
            $tagNames = json_decode($tagsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                $tagNames = [];
            }
        } catch (Exception $e) {
            error_log("Exception processing tags: " . $e->getMessage());
            $tagNames = [];
        }

        error_log("Processed tag names: " . print_r($tagNames, true));
        if (!is_array($tagNames)) {
            error_log("Tags not an array, type: " . gettype($tagNames));
            $tagNames = [];
        }
        
        // Create the question
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
    
    // Handle the update question action for QA forum
    protected function handleUpdateQuestion(Request $request)
    {
        $postId = $request->getBody()['post_id'] ?? '';
        $title = $request->getBody()['title'] ?? '';
        $body = $request->getBody()['body'] ?? '';
        $tagsJson = $request->getBody()['tags'] ?? '[]';
        
        // Basic validation
        if (empty($postId) || empty($title) || empty($body)) {
            // Redirect back with error message
            $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=empty_fields&action=edit');
            exit;
        }
        
        // Process the tags
        $tagsJson = $request->getBody()['tags'] ?? '[]';
        error_log("Update question - Raw tags JSON: " . $tagsJson);

        // Add HTML entity decoding before JSON parsing
        $tagsJson = html_entity_decode($tagsJson);
        error_log("Update question - After HTML decode: " . $tagsJson);

        try {
            $tagNames = json_decode($tagsJson, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                $tagNames = [];
            }
        } catch (Exception $e) {
            error_log("Exception processing tags: " . $e->getMessage());
            $tagNames = [];
        }

        error_log("Update question - Processed tag names: " . print_r($tagNames, true));
        if (!is_array($tagNames)) {
            error_log("Tags not an array, type: " . gettype($tagNames));
            $tagNames = [];
        }
        
        // Verify ownership of the question
        $qnaModel = new QnaPost();
        $question = $qnaModel->getQuestionById($postId);
        
        if (!$question || $question['user_id'] != $_SESSION['user_id']) {
            // Unauthorized attempt to edit someone else's question
            $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=unauthorized');
            exit;
        }
        
        // Update the question
        $success = $qnaModel->updateQuestion($postId, $title, $body, $tagNames);
        
        // Redirect back to the QA forum
        $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
        
        if ($success) {
            header('Location: ' . $redirectPath . '?success=question_updated');
        } else {
            header('Location: ' . $redirectPath . '?error=update_failed');
        }
        exit;
    }
    
    // Handle the delete question action for QA forum
    protected function handleDeleteQuestion(Request $request)
    {
        $postId = $request->getBody()['post_id'] ?? '';
        
        // Basic validation
        if (empty($postId)) {
            // Redirect back with error message
            $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=invalid_request');
            exit;
        }
        
        // Verify ownership of the question
        $qnaModel = new QnaPost();
        $question = $qnaModel->getQuestionById($postId);
        
        if (!$question || $question['user_id'] != $_SESSION['user_id']) {
            // Unauthorized attempt to delete someone else's question
            $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=unauthorized');
            exit;
        }
        
        // Delete the question
        $success = $qnaModel->deleteQuestion($postId);
        
        // Redirect back to the QA forum
        $redirectPath = '/UniHelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
        
        if ($success) {
            header('Location: ' . $redirectPath . '?success=question_deleted');
        } else {
            header('Location: ' . $redirectPath . '?error=delete_failed');
        }
        exit;
    }
    
    // Handle deletion of a question via direct GET request
    public function deleteQuestion($params)
    {
        // Check if question ID is provided
        if (!isset($params['id'])) {
            // Redirect to QA forum with error
            $redirectPath = '/unihelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=invalid_request');
            exit;
        }
        
        $questionId = $params['id'];
        
        // Verify ownership of the question
        $qnaModel = new QnaPost();
        $question = $qnaModel->getQuestionById($questionId);
        
        if (!$question || $question['user_id'] != $_SESSION['user_id']) {
            // Unauthorized attempt to delete someone else's question
            $redirectPath = '/unihelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
            header('Location: ' . $redirectPath . '?error=unauthorized');
            exit;
        }
        
        // Delete the question
        $success = $qnaModel->deleteQuestion($questionId);
        
        // Redirect back to the QA forum
        $redirectPath = '/unihelper/dashboard/' . $this->getDashboardType() . '/qa-forum';
        
        if ($success) {
            header('Location: ' . $redirectPath . '?success=question_deleted');
        } else {
            header('Location: ' . $redirectPath . '?error=delete_failed');
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
    
    // Get question data for editing
    public function getQuestionData($params)
    {
        // Check if question ID is provided
        if (!isset($params['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Question ID is required']);
            return;
        }
        
        $questionId = $params['id'];
        
        // Get question data
        $qnaModel = new QnaPost();
        $question = $qnaModel->getQuestionById($questionId);
        
        // Check if question exists
        if (!$question) {
            http_response_code(404);
            echo json_encode(['error' => 'Question not found']);
            return;
        }
        
        // Check if the current user is the author of the question
        if ($question['user_id'] != $_SESSION['user_id']) {
            http_response_code(403);
            echo json_encode(['error' => 'You are not authorized to edit this question']);
            return;
        }
        
        // Return question data as JSON
        header('Content-Type: application/json');
        echo json_encode($question);
    }
}