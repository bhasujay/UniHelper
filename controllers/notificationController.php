<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/notification.php';

use app\models\Notification;
use app\core\Request;

class NotificationController
{
    private $notificationModel;

    public function __construct()
    {
        $this->notificationModel = new Notification();
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

    private function resolveUserId(Request $request): ?int
    {
        $sessionUserId = $request->session('user_id');
        if (!empty($sessionUserId)) {
            return (int) $sessionUserId;
        }

        $requestUserId = $request->get('user_id');
        if (!empty($requestUserId) && is_numeric($requestUserId)) {
            return (int) $requestUserId;
        }

        return null;
    }

    // ---------------------------------------------------------------
    // GET  ?controller=notificationController&action=checkAny&user_id=X
    // ---------------------------------------------------------------
    public function checkAny(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            $this->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
            return;
        }

        try {
            $unreadCount = $this->notificationModel->checkAny($userId);
            $this->json([
                'success' => true,
                'unread_count' => (int) $unreadCount,
                'has_unread' => ((int) $unreadCount) > 0
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to check notifications.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------
    // GET  ?controller=notificationController&action=getUnread&user_id=X
    // ---------------------------------------------------------------
    public function getUnread(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            $this->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
            return;
        }

        try {
            $notifications = $this->notificationModel->getUnread($userId);
            $this->json([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch unread notifications.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------
    // GET  ?controller=notificationController&action=getRead&user_id=X
    // ---------------------------------------------------------------
    public function getRead(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            $this->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
            return;
        }

        try {
            $notifications = $this->notificationModel->getRead($userId);
            $this->json([
                'success' => true,
                'data' => $notifications
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch read notifications.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=notificationController&action=markAsRead
    // ---------------------------------------------------------------
    public function markAsRead(Request $request)
    {
        $notificationId = $request->get('notification_id');
        if (empty($notificationId) || !is_numeric($notificationId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid notification_id is required.'
            ], 400);
            return;
        }

        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            $this->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
            return;
        }

        $notification = $this->notificationModel->find((int) $notificationId);
        if (!$notification || (int) $notification['subscriber_id'] !== $userId) {
            $this->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
            return;
        }

        try {
            $ok = $this->notificationModel->markAsRead((int) $notificationId);
            $this->json([
                'success' => (bool) $ok,
                'message' => $ok ? 'Notification marked as read.' : 'No changes made.'
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to mark notification as read.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=notificationController&action=markAllAsRead
    // ---------------------------------------------------------------
    public function markAllAsRead(Request $request)
    {
        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            $this->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
            return;
        }

        try {
            $updatedRows = $this->notificationModel->markAllAsRead($userId);
            $this->json([
                'success' => true,
                'message' => 'Notifications marked as read.',
                'updated' => (int) $updatedRows
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to mark all notifications as read.'
            ], 500);
        }
    }

    // ---------------------------------------------------------------
    // POST ?controller=notificationController&action=delete
    // ---------------------------------------------------------------
    public function delete(Request $request)
    {
        $notificationId = $request->get('notification_id');
        if (empty($notificationId) || !is_numeric($notificationId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid notification_id is required.'
            ], 400);
            return;
        }

        $userId = $this->resolveUserId($request);
        if ($userId === null) {
            $this->json([
                'success' => false,
                'message' => 'User id is required.'
            ], 400);
            return;
        }

        $notification = $this->notificationModel->find((int) $notificationId);
        if (!$notification || (int) $notification['subscriber_id'] !== $userId) {
            $this->json([
                'success' => false,
                'message' => 'Notification not found.'
            ], 404);
            return;
        }

        try {
            $ok = $this->notificationModel->delete((int) $notificationId);
            $this->json([
                'success' => (bool) $ok,
                'message' => $ok ? 'Notification deleted.' : 'Failed to delete notification.'
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to delete notification.'
            ], 500);
        }
    }
}