<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/connection.php';
require_once dirname(__DIR__) . '/models/User.php';

use app\models\Connection;
use app\core\Request;
use app\models\User;

Class ConnectionController
{
    private $connectionModel;
    private $userModel;

    public function __construct()
    {
        $this->connectionModel = new Connection();
        $this->userModel = new User();
    }

    

    // ---------------------------------------------------------------
    // Emit JSON and exit
    // ---------------------------------------------------------------
    private function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    // ---------------------------------------------------------------
    // Helper: validate session and friend_id, return [$userId, $friendId]
    // or send an error response and return null.
    // ---------------------------------------------------------------
    private function resolveParties(Request $request): ?array
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return null;
        }

        $friendId = (int) $request->get('friend_id');
        if (!$friendId) {
            $this->json(['success' => false, 'message' => 'friend_id is required'], 400);
            return null;
        }

        if ($userId === $friendId) {
            $this->json(['success' => false, 'message' => 'You cannot connect with yourself'], 400);
            return null;
        }

        // Verify the target user exists
        if (!$this->userModel->findById($friendId)) {
            $this->json(['success' => false, 'message' => 'User not found'], 404);
            return null;
        }

        return [(int) $userId, $friendId];
    }

    // ---------------------------------------------------------------
    // GET  ?controller=connectionController&action=checkStatus&friend_id=X
    // ---------------------------------------------------------------
    public function checkStatus(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        try {
            $row = $this->connectionModel->checkStatus($userId, $friendId);
            if (!$row) {
                $this->json(['success' => true, 'status' => 'none']);
            } else {
                $this->json([
                    'success'      => true,
                    'status'       => $row['status'],
                    'initiated_by' => (int) $row['requester_id'],
                ]);
            }
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=connectionController&action=requestConnection
    // Body: friend_id
    // ---------------------------------------------------------------
    public function requestConnection(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        try {
            $existing = $this->connectionModel->checkStatus($userId, $friendId);
            if ($existing) {
                $this->json(['success' => false, 'message' => 'A connection already exists between these users'], 409);
                return;
            }

            $this->connectionModel->requestConnection($userId, $friendId);
            $this->json(['success' => true, 'message' => 'Connection request sent']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=connectionController&action=acceptConnection
    // Body: friend_id  (the requester)
    // The authenticated user is the receiver who is accepting.
    // ---------------------------------------------------------------
    public function acceptConnection(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        try {
            $updated = $this->connectionModel->acceptConnection($userId, $friendId);
            if (!$updated) {
                $this->json(['success' => false, 'message' => 'No pending request found from this user'], 404);
                return;
            }
            $this->json(['success' => true, 'message' => 'Connection accepted']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=connectionController&action=rejectConnection
    // Body: friend_id  (the requester)
    // The authenticated user is the receiver who is rejecting.
    // ---------------------------------------------------------------
    public function rejectConnection(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        try {
            $updated = $this->connectionModel->rejectConnection($userId, $friendId);
            if (!$updated) {
                $this->json(['success' => false, 'message' => 'No pending request found from this user'], 404);
                return;
            }
            $this->json(['success' => true, 'message' => 'Connection request rejected']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=connectionController&action=cancelConnection
    // Body: friend_id  (the receiver)
    // The authenticated user is the requester who is canceling.
    // ---------------------------------------------------------------
    public function cancelConnection(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        try {
            $deleted = $this->connectionModel->cancelConnection($userId, $friendId);
            if (!$deleted) {
                $this->json(['success' => false, 'message' => 'No pending request found to cancel'], 404);
                return;
            }
            $this->json(['success' => true, 'message' => 'Connection request canceled']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=connectionController&action=removeConnection
    // Body: friend_id
    // Either party can remove an accepted connection.
    // ---------------------------------------------------------------
    public function removeConnection(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        try {
            $deleted = $this->connectionModel->removeConnection($userId, $friendId);
            if (!$deleted) {
                $this->json(['success' => false, 'message' => 'No connection found between these users'], 404);
                return;
            }
            $this->json(['success' => true, 'message' => 'Connection removed']);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // GET  ?controller=connectionController&action=getConnections
    // Returns all accepted connections for the current user.
    // ---------------------------------------------------------------
    public function getConnections(Request $request): void
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return;
        }

        try {
            $connections = $this->connectionModel->getConnections((int) $userId);
            $this->json(['success' => true, 'data' => $connections]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // GET  ?controller=connectionController&action=getPendingRequests
    // Returns all pending incoming requests for the current user.
    // ---------------------------------------------------------------
    public function getPendingRequests(Request $request): void
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return;
        }

        try {
            $requests = $this->connectionModel->getPendingRequests((int) $userId);
            $this->json(['success' => true, 'data' => $requests]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // GET  ?controller=connectionController&action=getTargetUser&friend_id=X&state=public|private|friend
    // Returns user data according to privacy `state`.
    // If state=='friend' the caller must be an accepted connection.
    // ---------------------------------------------------------------
    public function getTargetUser(Request $request): void
    {
        $parties = $this->resolveParties($request);
        if ($parties === null) return;
        [$userId, $friendId] = $parties;

        $state = strtolower($request->get('state') ?? 'public');
        if (!in_array($state, ['private', 'public', 'friend'])) {
            $state = 'public';
        }

        if ($state === 'friend') {
            try {
                $row = $this->connectionModel->checkStatus($userId, $friendId);
            } catch (\Exception $e) {
                $this->json(['success' => false, 'message' => $e->getMessage()], 500);
                return;
            }
            if (!$row || $row['status'] !== 'accepted') {
                $this->json(['success' => false, 'message' => 'Friend-only data requested but users are not connected'], 403);
                return;
            }
        }

        try {
            $target = $this->connectionModel->getTargetUser($friendId, $state);
            if (!$target) {
                $this->json(['success' => false, 'message' => 'User not found'], 404);
                return;
            }
            $this->json(['success' => true, 'data' => $target]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // this to get user's friends that are already accpeted connection
    // GET  ?controller=connectionController&action=getFriends
    // ---------------------------------------------------------------
    public function getFriends(Request $request): void
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return;
        }

        try {
            // contract: [{user_id, name, profile_picture, role}, ...], max 100
            $friends = $this->connectionModel->getFriends((int) $userId, 100);
            $this->json(['success' => true, 'data' => $friends]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // this is to get user's pending connection requests that are sent by the user
    // GET  ?controller=connectionController&action=getPendingConnections
    // ---------------------------------------------------------------
    public function getPendingConnections(Request $request): void
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return;
        }

        try {
            // contract: [{user_id, name, profile_picture, role}, ...], max 100
            $pending = $this->connectionModel->getPendingConnections((int) $userId, 100);
            $this->json(['success' => true, 'data' => $pending]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // this is to get user's pending connection requests that are sent by other users
    // GET  ?controller=connectionController&action=getReceivedRequests
    // ---------------------------------------------------------------
    public function getReceivedRequests(Request $request): void
    {
        $userId = $request->session('user_id');
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return;
        }

        try {
            // contract: [{user_id, name, profile_picture, role}, ...], max 100
            $received = $this->connectionModel->getReceivedRequests((int) $userId, 100);
            $this->json(['success' => true, 'data' => $received]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // ---------------------------------------------------------------
    // this is to get user suggestions based on interests or mutual connections that are public accounts
    // GET  ?controller=connectionController&action=getSuggestions&type=X
    // ---------------------------------------------------------------
    public function getSuggestions(Request $request): void
    {
        $userId = $request->session('user_id');
        $type = $request->get('type') ?? 'mutual';
        if (!$userId) {
            $this->json(['success' => false, 'message' => 'Unauthorised'], 401);
            return;
        }

        if (strtolower((string) $type) !== 'mutual') {
            $this->json(['success' => false, 'message' => 'Only type=mutual is supported'], 400);
            return;
        }

        try {
            // contract: [{user_id, name, profile_picture, role}, ...], max 30
            // algorithm: random 10 direct friends -> friends of those friends -> dedupe/exclude self
            $suggestions = $this->connectionModel->getMutualSuggestions((int) $userId, 10, 30);
            $this->json(['success' => true, 'data' => $suggestions]);
        } catch (\Exception $e) {
            $this->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }


}
