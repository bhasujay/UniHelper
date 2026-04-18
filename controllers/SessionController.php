<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/Session_model.php';
require_once dirname(__DIR__) . '/models/User.php';

use app\core\Application;
use app\core\Request;
use app\models\Session_model;
use app\models\User;

class SessionController
{
    private $model;
    private $user;

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /UniHelper/home');
            exit;
        }
        $userModel = new User();
        $this->user = $userModel->findById($_SESSION['user_id']);
        if (!$this->user) {
            session_destroy();
            header('Location: /UniHelper/register');
            exit;
        }
        $this->model = new Session_model();
        // Auto-update session statuses on every controller instantiation
        $this->model->autoUpdateStatuses();
    }

    private function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    private function userId(): int
    {
        return (int)$this->user->id;
    }

    private function userUni(): ?int
    {
        $uni = $this->user->University ?? null;
        return ($uni !== null && $uni !== '') ? (int)$uni : null;
    }

    // ─── GET  getAllSessions ────────────────────────────
    public function getAllSessions(Request $request): void
    {
        $page   = max(1, (int)($request->get('page') ?? 1));
        $limit  = 12;
        $offset = ($page - 1) * $limit;

        try {
            $sessions = $this->model->getAllSessions($this->userId(), $this->userUni(), $limit, $offset);
            $this->json(['success' => true, 'data' => $sessions, 'page' => $page, 'count' => count($sessions)]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch sessions.'], 500);
        }
    }

    // ─── GET  getMySessions ────────────────────────────
    public function getMySessions(Request $request): void
    {
        $page   = max(1, (int)($request->get('page') ?? 1));
        $limit  = 12;
        $offset = ($page - 1) * $limit;

        try {
            $sessions = $this->model->getMySessions($this->userId(), $limit, $offset);
            $this->json(['success' => true, 'data' => $sessions, 'page' => $page, 'count' => count($sessions)]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch your sessions.'], 500);
        }
    }

    // ─── GET  getSubscribedSessions ────────────────────
    public function getSubscribedSessions(Request $request): void
    {
        try {
            $sessions = $this->model->getSubscribedSessions($this->userId());
            $this->json(['success' => true, 'data' => $sessions]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch subscribed sessions.'], 500);
        }
    }

    // ─── GET  getSession ───────────────────────────────
    public function getSession(Request $request): void
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid session ID.'], 400);
            return;
        }

        try {
            $session = $this->model->getSessionById($id, $this->userId());
            if (!$session) {
                $this->json(['success' => false, 'message' => 'Session not found.'], 404);
                return;
            }
            $this->json(['success' => true, 'data' => $session]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to load session.'], 500);
        }
    }

    // ─── GET  getSessionForm (returns rendered HTML) ───
    public function getSessionForm(Request $request): void
    {
        $sessionId = (int)($request->get('session_id') ?? 0);
        $isEditMode = $sessionId > 0;
        $formData = [];
        $errors = [];

        if ($isEditMode) {
            $session = $this->model->getSessionById($sessionId, $this->userId());
            if (!$session || (int)$session['user_id'] !== $this->userId()) {
                $this->json(['success' => false, 'message' => 'Session not found or unauthorized.'], 404);
                return;
            }
            $formData = [
                'title'            => $session['title'] ?? '',
                'description'      => $session['description'] ?? '',
                'major_id'         => $session['major_id'] ?? '',
                'major_name'       => $session['major_name'] ?? '',
                'audience'         => $session['audience'] ?? '',
                'session_link'     => $session['session_link'] ?? '',
                'tags'             => $session['tags'] ?? '',
                'scheduled_at'     => $session['scheduled_at'] ?? '',
                'duration_minutes' => $session['duration_minutes'] ?? 60,
                'session_id'       => $sessionId,
            ];
        }

        try {
            $majors = $this->model->getMajors();
        } catch (\Exception $e) {
            $majors = [];
        }

        $editingSessionId = $sessionId;
        $isModalContext = true;
        $formId = 'modalCreateSessionForm';
        $formClass = 'session-form js-modal-create-session-form';
        $formAction = '/UniHelper/api?controller=SessionController&action=submitSession';

        ob_start();
        include Application::$ROOT_DIR . '/views/components/session-form.php';
        $html = ob_get_clean();

        $this->json([
            'success' => true,
            'data'    => [
                'title' => $isEditMode ? 'Edit Session' : 'Create Session',
                'html'  => $html,
            ],
        ]);
    }

    // ─── POST  submitSession (create or update) ────────
    public function submitSession(Request $request): void
    {
        $sessionId = (int)($request->get('session_id') ?? 0);
        $isEdit = $sessionId > 0;

        // Validate
        $title       = trim((string)($request->get('title') ?? ''));
        $description = trim((string)($request->get('description') ?? ''));
        $majorId     = (int)($request->get('major_id') ?? 0);
        $majorName   = trim((string)($request->get('major_name') ?? ''));
        $audience    = trim((string)($request->get('audience') ?? ''));
        $link        = trim((string)($request->get('session_link') ?? ''));
        $tags        = trim((string)($request->get('tags') ?? ''));
        $date        = trim((string)($request->get('date') ?? ''));
        $time        = trim((string)($request->get('time') ?? ''));
        $duration    = (int)($request->get('duration_minutes') ?? 60);

        $errors = [];
        if ($title === '') $errors['title'] = 'Title is required.';
        if ($title !== '' && strlen($title) < 5) $errors['title'] = 'Title must be at least 5 characters.';
        if ($description === '') $errors['description'] = 'Description is required.';
        if ($description !== '' && strlen($description) < 10) $errors['description'] = 'Description must be at least 10 characters.';
        if ($majorName !== '' && $majorId <= 0) $errors['major_id'] = 'Please choose a valid major from suggestions.';
        if ($majorId > 0 && !$this->model->majorExists($majorId)) $errors['major_id'] = 'Selected major is invalid.';
        if (!in_array($audience, ['public', 'university_only', 'private'], true)) $errors['audience'] = 'Invalid audience.';
        if ($date === '') $errors['date'] = 'Date is required.';
        if ($time === '') $errors['time'] = 'Time is required.';
        if ($duration < 15 || $duration > 480) $errors['duration_minutes'] = 'Duration must be 15–480 minutes.';

        if (!empty($errors)) {
            $this->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors], 422);
            return;
        }

        $scheduledAt = $date . ' ' . $time . ':00';
        $data = [
            'user_id'          => $this->userId(),
            'title'            => $title,
            'description'      => $description,
            'major_id'         => $majorId > 0 ? $majorId : null,
            'university_id'    => $this->userUni(),
            'audience'         => $audience,
            'session_link'     => $link !== '' ? $link : null,
            'tags'             => $tags !== '' ? $tags : null,
            'scheduled_at'     => $scheduledAt,
            'duration_minutes' => $duration,
        ];

        try {
            if ($isEdit) {
                $ok = $this->model->updateSession($sessionId, $this->userId(), $data);
                if (!$ok) {
                    $this->json(['success' => false, 'message' => 'Session not found or unauthorized.'], 404);
                    return;
                }
                $session = $this->model->getSessionById($sessionId, $this->userId());
                $this->json(['success' => true, 'message' => 'Session updated.', 'data' => ['operation' => 'update', 'session' => $session]]);
            } else {
                $newId = $this->model->createSession($data);
                $session = $this->model->getSessionById($newId, $this->userId());
                $this->json(['success' => true, 'message' => 'Session created.', 'data' => ['operation' => 'create', 'session' => $session]]);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to save session.'], 500);
        }
    }

    // ─── POST  deleteSession ───────────────────────────
    public function deleteSession(Request $request): void
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid session ID.'], 400);
            return;
        }

        try {
            $ok = $this->model->deleteSession($id, $this->userId());
            if (!$ok) {
                $this->json(['success' => false, 'message' => 'Session not found or unauthorized.'], 404);
                return;
            }
            $this->json(['success' => true, 'message' => 'Session cancelled.']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to delete session.'], 500);
        }
    }

    // ─── POST  deleteCompletedSession ────────────────
    public function deleteCompletedSession(Request $request): void
    {
        $id = (int)($request->get('id') ?? 0);
        if ($id <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid session ID.'], 400);
            return;
        }

        try {
            $ok = $this->model->deleteCompletedSession($id, $this->userId());
            if (!$ok) {
                $this->json(['success' => false, 'message' => 'Completed or cancelled session not found or unauthorized.'], 404);
                return;
            }
            $this->json(['success' => true, 'message' => 'Completed or cancelled session deleted.']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to delete completed session.'], 500);
        }
    }

    // ─── POST  subscribe ───────────────────────────────
    public function subscribe(Request $request): void
    {
        $sessionId = (int)($request->get('session_id') ?? 0);
        if ($sessionId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid session ID.'], 400);
            return;
        }

        try {
            $result = $this->model->subscribe($this->userId(), $sessionId);
            $this->json(['success' => true, 'message' => 'Subscribed.', 'data' => $result]);
        } catch (\Exception $e) {
            $code = str_contains($e->getMessage(), 'your university') ? 403 : 400;
            $this->json(['success' => false, 'message' => $e->getMessage()], $code);
        }
    }

    // ─── POST  unsubscribe ─────────────────────────────
    public function unsubscribe(Request $request): void
    {
        $sessionId = (int)($request->get('session_id') ?? 0);
        if ($sessionId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid session ID.'], 400);
            return;
        }

        try {
            $result = $this->model->unsubscribe($this->userId(), $sessionId);
            $this->json(['success' => true, 'message' => 'Unsubscribed.', 'data' => $result]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to unsubscribe.'], 500);
        }
    }

    // ─── GET  getSubscribers ───────────────────────────
    public function getSubscribers(Request $request): void
    {
        $sessionId = (int)($request->get('session_id') ?? 0);
        if ($sessionId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid session ID.'], 400);
            return;
        }

        try {
            $subs = $this->model->getSubscribers($sessionId, $this->userId());
            $this->json(['success' => true, 'data' => $subs]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch subscribers.'], 500);
        }
    }

    // ─── POST  approveSubscriber ───────────────────────
    public function approveSubscriber(Request $request): void
    {
        $sessionId    = (int)($request->get('session_id') ?? 0);
        $subscriberId = (int)($request->get('subscriber_id') ?? 0);

        if ($sessionId <= 0 || $subscriberId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid IDs.'], 400);
            return;
        }

        try {
            $ok = $this->model->updateSubscriptionStatus($sessionId, $this->userId(), $subscriberId, 'approved');
            $this->json($ok
                ? ['success' => true, 'message' => 'Subscriber approved.']
                : ['success' => false, 'message' => 'Not found or unauthorized.'], $ok ? 200 : 404);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed.'], 500);
        }
    }

    // ─── POST  rejectSubscriber ────────────────────────
    public function rejectSubscriber(Request $request): void
    {
        $sessionId    = (int)($request->get('session_id') ?? 0);
        $subscriberId = (int)($request->get('subscriber_id') ?? 0);

        if ($sessionId <= 0 || $subscriberId <= 0) {
            $this->json(['success' => false, 'message' => 'Invalid IDs.'], 400);
            return;
        }

        try {
            $ok = $this->model->updateSubscriptionStatus($sessionId, $this->userId(), $subscriberId, 'rejected');
            $this->json($ok
                ? ['success' => true, 'message' => 'Subscriber rejected.']
                : ['success' => false, 'message' => 'Not found or unauthorized.'], $ok ? 200 : 404);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed.'], 500);
        }
    }

    // ─── GET  searchSessions ───────────────────────────
    public function searchSessions(Request $request): void
    {
        $query = trim((string)($request->get('query') ?? ''));
        $tab   = trim((string)($request->get('tab') ?? 'all-sessions'));
        $page  = max(1, (int)($request->get('page') ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        if (strlen($query) < 2) {
            $this->json(['success' => false, 'message' => 'Query must be at least 2 characters.'], 400);
            return;
        }

        try {
            $sessions = $this->model->searchSessions($query, $this->userId(), $this->userUni(), $tab, $limit, $offset);
            $this->json(['success' => true, 'data' => $sessions, 'page' => $page, 'count' => count($sessions)]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Search failed.'], 500);
        }
    }

    // ─── GET  searchAllSessions ──────────────────────
    public function searchAllSessions(Request $request): void
    {
        $query = trim((string)($request->get('query') ?? ''));
        $page  = max(1, (int)($request->get('page') ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        if (strlen($query) < 2) {
            $this->json(['success' => false, 'message' => 'Query must be at least 2 characters.'], 400);
            return;
        }

        try {
            $sessions = $this->model->searchAllSessions($query, $this->userId(), $this->userUni(), $limit, $offset);
            $this->json(['success' => true, 'data' => $sessions, 'page' => $page, 'count' => count($sessions)]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Search failed.'], 500);
        }
    }

    // ─── GET  searchMySessions ───────────────────────
    public function searchMySessions(Request $request): void
    {
        $query = trim((string)($request->get('query') ?? ''));
        $page  = max(1, (int)($request->get('page') ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        if (strlen($query) < 2) {
            $this->json(['success' => false, 'message' => 'Query must be at least 2 characters.'], 400);
            return;
        }

        try {
            $sessions = $this->model->searchMySessions($query, $this->userId(), $limit, $offset);
            $this->json(['success' => true, 'data' => $sessions, 'page' => $page, 'count' => count($sessions)]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Search failed.'], 500);
        }
    }

    // ─── GET  searchSubscribedSessions ───────────────
    public function searchSubscribedSessions(Request $request): void
    {
        $query = trim((string)($request->get('query') ?? ''));
        $page  = max(1, (int)($request->get('page') ?? 1));
        $limit = 12;
        $offset = ($page - 1) * $limit;

        if (strlen($query) < 2) {
            $this->json(['success' => false, 'message' => 'Query must be at least 2 characters.'], 400);
            return;
        }

        try {
            $sessions = $this->model->searchSubscribedSessions($query, $this->userId(), $limit, $offset);
            $this->json(['success' => true, 'data' => $sessions, 'page' => $page, 'count' => count($sessions)]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Search failed.'], 500);
        }
    }

    // ─── GET  searchMajors ───────────────────────────
    public function searchMajors(Request $request): void
    {
        $query = trim((string)($request->get('query') ?? ''));
        $limit = (int)($request->get('limit') ?? 12);
        $limit = max(1, min(20, $limit));

        if ($query === '') {
            $this->json(['success' => true, 'data' => []]);
            return;
        }

        try {
            $majors = $this->model->searchMajors($query, $limit);
            $this->json(['success' => true, 'data' => $majors]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to search majors.'], 500);
        }
    }

    // ─── GET  getMajors ────────────────────────────────
    public function getMajors(Request $request): void
    {
        try {
            $majors = $this->model->getMajors();
            $this->json(['success' => true, 'data' => $majors]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => 'Failed to fetch majors.'], 500);
        }
    }
}