<?php

namespace app\models;
use app\core\Database;
use PDO;

require_once dirname(__DIR__) . '/models/base-model.php';


class Notification extends BaseModel
{
    protected $table = 'notifications';
    private const ALLOWED_MODULES = ['qa', 'connection', 'session', 'other'];

    public function __construct()
    {
        parent::__construct();
    }

    public function checkAny($userId)
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE subscriber_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (int) $stmt->fetchColumn();
    }

    public function getUnread($userId)
    {
        $sql = "SELECT id, subscriber_id AS user_id, message, module AS type, url AS link, is_read, created_at
                FROM {$this->table}
                WHERE subscriber_id = :user_id AND is_read = 0
                ORDER BY created_at DESC, id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRead($userId)
    {
        $sql = "SELECT id, subscriber_id AS user_id, message, module AS type, url AS link, is_read, created_at
                FROM {$this->table}
                WHERE subscriber_id = :user_id AND is_read = 1
                ORDER BY created_at DESC, id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($notificationId)
    {
        return $this->update((int) $notificationId, ['is_read' => 1]);
    }

    public function markAllAsRead($userId)
    {
        $sql = "UPDATE {$this->table} SET is_read = 1 WHERE subscriber_id = :user_id AND is_read = 0";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function findRecipientIdsByRoles(array $roles, int $excludeUserId = 0): array
    {
        $normalizedRoles = [];
        foreach ($roles as $role) {
            $value = trim((string) $role);
            if ($value !== '') {
                $normalizedRoles[$value] = true;
            }
        }

        $normalizedRoles = array_keys($normalizedRoles);
        if (empty($normalizedRoles)) {
            return [];
        }

        $placeholders = [];
        $params = [];
        foreach ($normalizedRoles as $index => $role) {
            $placeholder = ':role_' . $index;
            $placeholders[] = $placeholder;
            $params[$placeholder] = $role;
        }

        $sql = 'SELECT id FROM users WHERE role IN (' . implode(', ', $placeholders) . ')';
        if ($excludeUserId > 0) {
            $sql .= ' AND id <> :exclude_user_id';
            $params[':exclude_user_id'] = $excludeUserId;
        }

        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            if ($key === ':exclude_user_id') {
                $stmt->bindValue($key, (int) $value, PDO::PARAM_INT);
            } else {
                $stmt->bindValue($key, (string) $value, PDO::PARAM_STR);
            }
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return array_map('intval', $rows ?: []);
    }

    public function createMany(array $userIds, string $message, string $module = 'other', ?string $link = null): int
    {
        $normalizedIds = [];
        foreach ($userIds as $userId) {
            $id = (int) $userId;
            if ($id > 0) {
                $normalizedIds[$id] = true;
            }
        }

        $normalizedIds = array_keys($normalizedIds);
        if (empty($normalizedIds)) {
            return 0;
        }

        $safeModule = in_array($module, self::ALLOWED_MODULES, true) ? $module : 'other';
        $sql = "INSERT INTO {$this->table} (subscriber_id, module, message, url, is_read) VALUES (:subscriber_id, :module, :message, :url, 0)";
        $stmt = $this->db->prepare($sql);

        $inserted = 0;
        foreach ($normalizedIds as $userId) {
            $ok = $stmt->execute([
                'subscriber_id' => $userId,
                'module' => $safeModule,
                'message' => $message,
                'url' => $link,
            ]);

            if ($ok) {
                $inserted++;
            }
        }

        return $inserted;
    }

    public function createPostLikeNotification(int $postOwnerId, int $actorUserId, string $actorName, int $postId, string $postTitle = ''): int
    {
        if ($postOwnerId <= 0 || $actorUserId <= 0 || $postId <= 0) {
            return 0;
        }

        if ($postOwnerId === $actorUserId) {
            return 0;
        }

        $safeActorName = trim($actorName);
        if ($safeActorName === '') {
            $safeActorName = 'Someone';
        }

        $safeTitle = trim($postTitle);
        if ($safeTitle !== '') {
            $safeTitle = preg_replace('/\s+/', ' ', $safeTitle);
            if (mb_strlen($safeTitle) > 80) {
                $safeTitle = mb_substr($safeTitle, 0, 77) . '...';
            }
        }

        $message = $safeTitle !== ''
            ? $safeActorName . ' liked your post: "' . $safeTitle . '"'
            : $safeActorName . ' liked your post.';

        // Link includes source and item id so the feed page can focus the specific card.
        $deepLink = '/unihelper/announcements?source=post&post=' . $postId;

        return $this->createMany([$postOwnerId], $message, 'other', $deepLink);
    }

    public function delete($notificationId)
    {
        return parent::delete((int) $notificationId);
    }

}