<?php

namespace app\models;

use app\core\Database;
use PDO;

// this is a lighteight model that is only used to insert notifications
// this will be used by many models to insert notifications, so it is not tied to any specific controller
// it does not need to extend the base model, as it does not need to perform any complex queries or operations, it only needs to insert notifications into the database
// so we make this a simple class that can be used by any model that needs to insert notifications, without the overhead of the base model

class Notify
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function insertNotification($userId, $message, $type = 'other', $link = null)
    {
        $allowedModules = ['qa', 'connection', 'session', 'other'];
        $module = in_array($type, $allowedModules, true) ? $type : 'other';

        $sql = 'INSERT INTO notifications (subscriber_id, module, message, url, is_read) VALUES (:subscriber_id, :module, :message, :url, 0)';
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':subscriber_id', (int) $userId, PDO::PARAM_INT);
        $stmt->bindValue(':module', $module, PDO::PARAM_STR);
        $stmt->bindValue(':message', (string) $message, PDO::PARAM_STR);
        $stmt->bindValue(':url', $link, $link === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->execute();

        return (int) $this->db->lastInsertId();
    }

}