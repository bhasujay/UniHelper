<?php

namespace app\models;
use app\core\Database;
use PDO;

require_once dirname(__DIR__) . '/models/base-model.php';


class Notification extends BaseModel
{
    protected $table = 'notifications';

    public function __construct()
    {
        parent::__construct();
    }

    public function checkAny($userId)
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE subscriber_id = :user_id AND is_read = 0 LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user_id', (int) $userId, PDO::PARAM_INT);
        $stmt->execute();

        return (bool) $stmt->fetchColumn();
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

    public function delete($notificationId)
    {
        return parent::delete((int) $notificationId);
    }

}