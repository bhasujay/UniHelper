<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/Session_model.php';
require_once dirname(__DIR__, 1) . '/models/User.php';

use app\core\Application;
use app\core\Request;
use app\models\Session_model;
use app\models\User;

class SessionController
{
    protected $sessionModel;
    protected $user;

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

        $this->sessionModel = new Session_model();
    }

    // GET /create-session - Display create session form
    public function index()
    {
        $user = $this->user;
        $userData = [
            'userId' => $this->user->id,
            'university' => $this->user->University ?? ''
        ];
        
        include Application::$ROOT_DIR . '/views/components/create-session.php';
    }

    // POST /create-session - Handle form submission
    public function store(Request $request)
    {
        $errors = [];

        // Get form data
        $title = $request->get('title');
        $subject = $request->get('subject');
        $description = $request->get('description');
        $date = $request->get('date');
        $time = $request->get('time');
        $duration = $request->get('duration');
        $sessionLink = $request->get('sessionLink');
        $audience = $request->get('audience');
        $tags = $request->get('tags');

        // Validation: Title
        if (empty($title)) {
            $errors['title'] = 'Session title is required.';
        } elseif (strlen($title) > 255) {
            $errors['title'] = 'Session title must not exceed 255 characters.';
        }

        // Validation: Subject
        if (empty($subject)) {
            $errors['subject'] = 'Subject is required.';
        }

        // Validation: Description
        if (empty($description)) {
            $errors['description'] = 'Description is required.';
        }

        // Validation: Date
        if (empty($date)) {
            $errors['date'] = 'Date is required.';
        } else {
            $selectedDate = strtotime($date);
            $today = strtotime(date('Y-m-d'));
            if ($selectedDate < $today) {
                $errors['date'] = 'Date must be today or in the future.';
            }
        }

        // Validation: Time
        if (empty($time)) {
            $errors['time'] = 'Time is required.';
        }

        // Validation: Duration
        if (empty($duration)) {
            $errors['duration'] = 'Duration is required.';
        } elseif (!is_numeric($duration) || $duration <= 0) {
            $errors['duration'] = 'Duration must be a positive number.';
        }

        // Validation: Audience
        if (empty($audience) || !in_array($audience, ['my_university', 'all_universities'])) {
            $errors['audience'] = 'Please select a valid audience option.';
        }

        // If there are validation errors, reload the form with errors
        if (!empty($errors)) {
            $user = $this->user;
            $userData = [
                'userId' => $this->user->id,
                'university' => $this->user->University ?? ''
            ];
            $formData = [
                'title' => $title,
                'subject' => $subject,
                'description' => $description,
                'date' => $date,
                'time' => $time,
                'duration' => $duration,
                'sessionLink' => $sessionLink,
                'audience' => $audience,
                'tags' => $tags
            ];

            include Application::$ROOT_DIR . '/views/components/create-session.php';
            return;
        }

        // Prepare data for database
        $sessionData = [
            'user_id' => $this->user->id,
            'title' => $title,
            'subject' => $subject,
            'description' => $description,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
            'session_link' => $sessionLink ?? null,
            'audience' => $audience,
            'university' => $this->user->University ?? '',
            'tags' => $tags ?? null
        ];

        try {
            // Create the session
            $sessionId = $this->sessionModel->create($sessionData);
            // Redirect to Peer Learning page with success
            header('Location: /UniHelper/peer-learning');
            exit;
        } catch (\Exception $e) {
            // Handle database error
            $user = $this->user;
            $userData = [
                'userId' => $this->user->id,
                'university' => $this->user->University ?? ''
            ];
            $formData = [
                'title' => $title,
                'subject' => $subject,
                'description' => $description,
                'date' => $date,
                'time' => $time,
                'duration' => $duration,
                'sessionLink' => $sessionLink,
                'audience' => $audience,
                'tags' => $tags
            ];
            $errors['form'] = 'An error occurred while creating the session. Please try again.';

            include Application::$ROOT_DIR . '/views/components/create-session.php';
            return;
        }
    }

    // GET /api/sessions - Fetch all sessions with pagination (JSON)
    public function getAllSessions(Request $request)
    {
        header('Content-Type: application/json');
        
        try {
            $page = $request->get('page') ?? 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sessions = $this->sessionModel->findAll([], $limit, $offset);
            
            // Mark expired sessions
            $sessions = $this->markExpiredSessions($sessions);
            
            echo json_encode([
                'success' => true,
                'data' => $sessions,
                'count' => count($sessions),
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch sessions.'
            ]);
        }
    }

    // GET /api/sessions/my - Fetch user's sessions (JSON) - includes expired
    public function getMyessions(Request $request)
    {
        header('Content-Type: application/json');
        
        try {
            $page = $request->get('page') ?? 1;
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sessions = $this->sessionModel->findByUserId($this->user->id, $limit, $offset);
            
            // Add expired flag
            $sessions = $this->addExpiredFlag($sessions);
            
            echo json_encode([
                'success' => true,
                'data' => $sessions,
                'count' => count($sessions),
                'page' => $page,
                'limit' => $limit
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch your sessions.'
            ]);
        }
    }

    // POST /api/sessions/delete - Delete a session (JSON)
    public function deleteSession(Request $request)
    {
        header('Content-Type: application/json');
        
        try {
            $sessionId = $request->get('id');
            
            if (!$sessionId) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session ID is required.'
                ]);
                return;
            }
            
            // Get the session
            $session = $this->sessionModel->find($sessionId);
            
            if (!$session) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session not found.'
                ]);
                return;
            }
            
            // Check if user is the creator
            if ($session['user_id'] != $this->user->id) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'You are not authorized to delete this session.'
                ]);
                return;
            }
            
            // Delete the session
            $this->sessionModel->softDelete($sessionId);
            
            echo json_encode([
                'success' => true,
                'message' => 'Session deleted successfully.'
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to delete session.'
            ]);
        }
    }

    /**
     * Helper method to mark expired sessions
     * Checks if date/time has passed and auto-expires them
     */
    private function markExpiredSessions($sessions)
    {
        $now = new \DateTime();
        
        foreach ($sessions as &$session) {
            $sessionDateTime = new \DateTime($session['date'] . ' ' . $session['time']);
            
            // If session time has passed, mark as expired
            if ($sessionDateTime < $now && is_null($session['deleted_at'])) {
                $this->sessionModel->markAsExpired($session['id']);
                $session['deleted_at'] = date('Y-m-d H:i:s');
            }
        }
        
        return $sessions;
    }

    /**
     * Helper method to add expired flag to sessions
     * Used for "My Sessions" to show expired label
     */
    private function addExpiredFlag($sessions)
    {
        $now = new \DateTime();
        
        foreach ($sessions as &$session) {
            $sessionDateTime = new \DateTime($session['date'] . ' ' . $session['time']);
            $session['is_expired'] = $sessionDateTime < $now ? 1 : 0;
            
            // If expired and not already marked, mark it now
            if ($session['is_expired'] && is_null($session['deleted_at'])) {
                $this->sessionModel->markAsExpired($session['id']);
                $session['deleted_at'] = date('Y-m-d H:i:s');
            }
        }
        
        return $sessions;
    }
}