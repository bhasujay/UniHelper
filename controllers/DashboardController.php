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

class DashboardController
{
    protected $activeComponent = '';
    protected $user;
    protected $role_data;
    protected $role_title;

    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /UniHelper/home');
            exit;
        }

        // Get the user from database
        $userModel = new User();
        $this->user = $userModel->findById($_SESSION['user_id']);
        
        if (!$this->user) {
            session_destroy();
            header('Location: /UniHelper/register');
            exit;
        }

        switch ($this->user->role) {
            case 'role-applicant':
                $this->role_title = 'Applicant';
                break;
            case 'role-undergrad':
                $this->role_title = 'Undergraduate';
                break;
            case 'role-profile':
                $this->role_title = 'Profile';
                break;
            case 'role-admin':
                $this->role_title = 'Administrator';
                break;
            default:
                $this->role_title = 'Dashboard';
        }

        $this->role_data = require Application::$ROOT_DIR . '/config/sidebar_config.php';
    }

    // Default entry point for dashboard
    public function index()
    {
        // Set default active component
        $this->activeComponent = $this->role_data[$this->user->role][0]['component'];
        
        // Load default component
        $content = $this->loadComponent($this->activeComponent);
        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    // Renders a specific component based on URL parameter
    public function renderComponent($params)
    {
        $component = $params['component'] ?? $this->role_data[$this->user->role][0]['component'];
        
        // Set active component
        $this->activeComponent = $component;

        // Load the requested component
        $content = $this->loadComponent($component);
        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    // Loads a component file
    protected function loadComponent($componentName)
    {
        $componentPath = Application::$ROOT_DIR . "/views/components/{$componentName}.php";

        if (!file_exists($componentPath)) {
            $componentPath = Application::$ROOT_DIR . "/views/{$componentName}.php";
        }
        
        if (!file_exists($componentPath)) {
            return "<div class='error'>Component {$componentName} not found</div>";
        }

        $user = $this->user; // Make user available to components
        
        // Capture the component content
        ob_start();
        include $componentPath;
        return ob_get_clean();
    }

    // Renders the dashboard with the given content
    protected function renderDashboard($content = NULL, $sidebar = NULL)
    {
        $user = $this->user; // Make user available to the dashboard template        
        $activeComponent = $this->activeComponent; // Make active component available to the dashboard template
        $role_title = $this->role_title; // Make role title available to the dashboard template
        
        // Make content available to the dashboard template
        include Application::$ROOT_DIR . "/views/dashboard.php";
    }

    // Profile card index
    public function profileIndex(Request $request)
    {
        $pathParts = explode('/', $request->getPath());
        $req = end($pathParts);
        $component = '';
        if ($req === 'edit') {
            $component = 'edit-component';
        } elseif ($req === 'view') {
            $component = 'profile-component';
        }

        $activeComponent = $component;
        $content = $this->loadComponent('profile/' . $component);

        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    // profile edit component action handlers
    public function profileUpdate(Request $request)
    {
        // Get form data
        $this->user->firstName = $request->get('firstName');
        $this->user->lastName = $request->get('lastName');
        $this->user->email = $request->get('email');
        $this->user->phone = $request->get('phone');
        $this->user->public = $request->get('public') ? 1 : 0;
        
        // Role-specific fields
        if ($this->user->role === 'role-applicant') {
            $this->user->alYear = $request->get('alYear');
        } elseif ($this->user->role === 'role-undergrad') {
            $this->user->University = $request->get('undergradUniversity');
            $this->user->major = $request->get('major');
        } elseif ($this->user->role === 'role-profile') {
            $this->user->University = $request->get('profileUniversity');
            $this->user->profileRole = $request->get('profileRole');
        }

        // Handle profile picture upload
        $profilePicture = $request->get('profilePicture');
        if ($profilePicture && isset($profilePicture['tmp_name']) && is_uploaded_file($profilePicture['tmp_name'])) {
            // Delete old profile picture if exists
            if (!empty($this->user->profilePicture)) {
                $oldPicturePath = Application::$ROOT_DIR . '/public' . $this->user->profilePicture;
                if (file_exists($oldPicturePath)) {
                    unlink($oldPicturePath);
                }
            }

            $emailUsername = explode('@', $this->user->email)[0];
            $targetFile = Application::$ROOT_DIR . '/public/uploads/profilePictures/' . $emailUsername . '.' . pathinfo($profilePicture['name'], PATHINFO_EXTENSION);

            if (move_uploaded_file($profilePicture['tmp_name'], $targetFile)) {
                $this->user->profilePicture = '/uploads/profilePictures/' . basename($targetFile);
            }
        } else {
            // If profile picture should be removed (set to null)
            if ($request->get('removeProfilePicture') === '1' || empty($this->user->profilePicture)) {
                // Delete existing profile picture file
                if (!empty($this->user->profilePicture)) {
                    $existingPicturePath = Application::$ROOT_DIR . '/public' . $this->user->profilePicture;
                    if (file_exists($existingPicturePath)) {
                        unlink($existingPicturePath);
                    }
                }
                $this->user->profilePicture = null;
            }
        }

        // Update user
        $this->user->update();

        // Redirect to profile view
        header('Location: /UniHelper/profile/view');
        exit;
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