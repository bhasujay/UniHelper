<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/badge-user.php';

use app\models\badgeUser;
use app\core\Request;

class badgeController
{
    private $badgeUser;

    public function __construct()
    {
        $this->badgeUser = new badgeUser();
    }

    public function getBadgesForUser(Request $request)
    {
        $userId = $request->get('user_id');

        if (!$userId) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Missing user_id parameter']);
            return;
        }

        try {
            $badges = $this->badgeUser->getBadgesForUser($userId);
            header('Content-Type: application/json');

            if ($badges === false) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Server error']);
                return;
            }

            echo json_encode(['success' => true, 'badges' => $badges]);
        } catch (\Exception $e) {
            error_log('badgeController getBadgesForUser error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Server error']);
        }
    }
}