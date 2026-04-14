<?php

namespace app\models;

use app\core\Database;

class badgeUser
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // get all badges for a user
    public function getBadgesForUser($userId)
    {
        try {
            $sql = "SELECT b.name, b.description, b.image_url, ub.awarded_at 
                    FROM user_badges ub 
                    JOIN badges b ON ub.badge_id = b.id 
                    WHERE ub.user_id = :user_id 
                    ORDER BY ub.awarded_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            error_log("BadgeUser getBadgesForUser error: " . $e->getMessage());
            return false;
        }
    }
}