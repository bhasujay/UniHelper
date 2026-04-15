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
        [$errors, $formData, $sessionData] = $this->validateSessionPayload($request);

        // If there are validation errors, reload the form with errors
        if (!empty($errors)) {
            $user = $this->user;
            $userData = [
                'userId' => $this->user->id,
                'university' => $this->user->University ?? ''
            ];
            $isEditMode = false;
            $editingSessionId = 0;

            include Application::$ROOT_DIR . '/views/components/create-session.php';
            return;
        }

        try {
            // Create the session
            $this->sessionModel->create($sessionData);
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
            $isEditMode = false;
            $editingSessionId = 0;
            $errors['form'] = 'An error occurred while creating the session. Please try again.';

            include Application::$ROOT_DIR . '/views/components/create-session.php';
            return;
        }
    }

    // GET /api?controller=SessionController&action=getSessionForEdit&id={id}
    public function getSessionForEdit(Request $request)
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

            $session = $this->sessionModel->find($sessionId);

            if (!$session) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'error' => 'Session not found.'
                ]);
                return;
            }

            if ((int)$session['user_id'] !== (int)$this->user->id) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'error' => 'You are not authorized to edit this session.'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'data' => $session
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Failed to fetch session details.'
            ]);
        }
    }

    // POST /api?controller=SessionController&action=update
    public function update(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            header('Location: /UniHelper/peer-learning');
            exit;
        }

        $session = $this->sessionModel->find($sessionId);
        if (!$session || (int)$session['user_id'] !== (int)$this->user->id) {
            header('Location: /UniHelper/peer-learning');
            exit;
        }

        [$errors, $formData, $sessionData] = $this->validateSessionPayload($request, (int)$sessionId);

        if (!empty($errors)) {
            $user = $this->user;
            $userData = [
                'userId' => $this->user->id,
                'university' => $this->user->University ?? ''
            ];
            $isEditMode = true;
            $editingSessionId = (int)$sessionId;

            include Application::$ROOT_DIR . '/views/components/create-session.php';
            return;
        }

        try {
            $this->sessionModel->updateByOwner((int)$sessionId, (int)$this->user->id, $sessionData);
            header('Location: /UniHelper/peer-learning');
            exit;
        } catch (\Exception $e) {
            $user = $this->user;
            $userData = [
                'userId' => $this->user->id,
                'university' => $this->user->University ?? ''
            ];
            $isEditMode = true;
            $editingSessionId = (int)$sessionId;
            $errors['form'] = 'An error occurred while updating the session. Please try again.';

            include Application::$ROOT_DIR . '/views/components/create-session.php';
            return;
        }
    }

    // GET /api/sessions - Fetch all sessions with pagination (JSON)
    public function getAllSessions(Request $request)
    {
        header('Content-Type: application/json');
        
        try {
            $page = max(1, (int)($request->get('page') ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sessions = $this->sessionModel->findAll(
                [],
                $limit,
                $offset,
                (int)$this->user->id,
                $this->user->University ?? null
            );
            
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
            $page = max(1, (int)($request->get('page') ?? 1));
            $limit = 10;
            $offset = ($page - 1) * $limit;
            
            $sessions = $this->sessionModel->findByUserId($this->user->id, $limit, $offset, (int)$this->user->id);
            
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

    // POST /api?controller=SessionController&action=subscribeAction
    public function subscribeAction(Request $request)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $sessionId = filter_var($request->get('session_id'), FILTER_VALIDATE_INT);

        if ($sessionId === false || $sessionId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid session ID'
            ]);
            return;
        }

        try {
            $state = $this->sessionModel->subscribe($userId, (int)$sessionId);

            echo json_encode([
                'success' => true,
                'message' => 'Subscription updated successfully',
                'data' => $state
            ]);
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'only subscribe to sessions from your university') ? 403 : 500;
            http_response_code($statusCode);
            echo json_encode([
                'success' => false,
                'message' => $statusCode === 403 ? $e->getMessage() : 'Failed to subscribe'
            ]);
        }
    }

    // POST /api?controller=SessionController&action=unsubscribeAction
    public function unsubscribeAction(Request $request)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'User not authenticated'
            ]);
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $sessionId = filter_var($request->get('session_id'), FILTER_VALIDATE_INT);

        if ($sessionId === false || $sessionId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid session ID'
            ]);
            return;
        }

        try {
            $state = $this->sessionModel->unsubscribe($userId, (int)$sessionId);

            echo json_encode([
                'success' => true,
                'message' => 'Unsubscribed successfully',
                'data' => $state
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to unsubscribe'
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

    // GET /api?controller=SessionController&action=getSubscriberList&session_id={id}
    public function getSubscriberList(Request $request)
    {
        header('Content-Type: application/json');

        $sessionId = filter_var($request->get('session_id'), FILTER_VALIDATE_INT);

        if ($sessionId === false || $sessionId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid session ID'
            ]);
            return;
        }

        try {
            if (!$this->sessionModel->isPrivateSessionOwnedBy((int)$sessionId, (int)$this->user->id)) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'You are not authorized to view this subscriber list.'
                ]);
                return;
            }

            $subscribers = $this->sessionModel->getSubscriberList((int)$sessionId, (int)$this->user->id);

            echo json_encode([
                'success' => true,
                'data' => $subscribers
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to fetch subscriber list.'
            ]);
        }
    }

    // POST /api?controller=SessionController&action=approveSubscriberAction
    public function approveSubscriberAction(Request $request)
    {
        $this->handleSubscriberDecision($request, 'approved');
    }

    // POST /api?controller=SessionController&action=rejectSubscriberAction
    public function rejectSubscriberAction(Request $request)
    {
        $this->handleSubscriberDecision($request, 'rejected');
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

    /**
     * Validate create/update payload and return [errors, formData, sessionData]
     */
    private function validateSessionPayload(Request $request, ?int $sessionId = null): array
    {
        $errors = [];

        $title = trim((string)($request->get('title') ?? ''));
        $subject = trim((string)($request->get('subject') ?? ''));
        $description = trim((string)($request->get('description') ?? ''));
        $date = trim((string)($request->get('date') ?? ''));
        $time = trim((string)($request->get('time') ?? ''));
        $duration = trim((string)($request->get('duration') ?? ''));
        $sessionLink = trim((string)($request->get('sessionLink') ?? ''));
        $audience = trim((string)($request->get('audience') ?? ''));
        $tags = trim((string)($request->get('tags') ?? ''));

        if ($title === '') {
            $errors['title'] = 'Session title is required.';
        } elseif (strlen($title) > 255) {
            $errors['title'] = 'Session title must not exceed 255 characters.';
        }

        if ($subject === '') {
            $errors['subject'] = 'Subject is required.';
        }

        if ($description === '') {
            $errors['description'] = 'Description is required.';
        }

        if ($date === '') {
            $errors['date'] = 'Date is required.';
        } else {
            $selectedDate = strtotime($date);
            $today = strtotime(date('Y-m-d'));
            if ($selectedDate < $today) {
                $errors['date'] = 'Date must be today or in the future.';
            }
        }

        if ($time === '') {
            $errors['time'] = 'Time is required.';
        }

        if ($duration === '') {
            $errors['duration'] = 'Duration is required.';
        } elseif (!is_numeric($duration) || (float)$duration <= 0) {
            $errors['duration'] = 'Duration must be a positive number.';
        }

        if ($audience === '' || !in_array($audience, ['my_university', 'all_universities', 'private'], true)) {
            $errors['audience'] = 'Please select a valid audience option.';
        }

        $formData = [
            'title' => $title,
            'subject' => $subject,
            'description' => $description,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
            'sessionLink' => $sessionLink,
            'audience' => $audience,
            'tags' => $tags,
            'session_id' => $sessionId
        ];

        $sessionData = [
            'user_id' => $this->user->id,
            'title' => $title,
            'subject' => $subject,
            'description' => $description,
            'date' => $date,
            'time' => $time,
            'duration' => $duration,
            'session_link' => $sessionLink !== '' ? $sessionLink : null,
            'audience' => $audience,
            'university' => $this->user->University ?? '',
            'tags' => $tags !== '' ? $tags : null
        ];

        return [$errors, $formData, $sessionData];
    }

    private function handleSubscriberDecision(Request $request, string $status): void
    {
        header('Content-Type: application/json');

        $sessionId = filter_var($request->get('session_id'), FILTER_VALIDATE_INT);
        $subscriberId = filter_var($request->get('subscriber_id'), FILTER_VALIDATE_INT);

        if ($sessionId === false || $sessionId <= 0 || $subscriberId === false || $subscriberId <= 0) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid session_id or subscriber_id.'
            ]);
            return;
        }

        try {
            $result = $this->sessionModel->updateSubscriberStatus(
                (int)$sessionId,
                (int)$this->user->id,
                (int)$subscriberId,
                $status
            );

            if (!$result) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Subscriber or session not found.'
                ]);
                return;
            }

            echo json_encode([
                'success' => true,
                'message' => $status === 'approved' ? 'Subscriber approved.' : 'Subscriber rejected.',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update subscriber status.'
            ]);
        }
    }
}