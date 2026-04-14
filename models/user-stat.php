<?php

namespace app\models;

require_once __DIR__ . '/badger.php';

use app\core\Database;
use app\models\badger;

class UserStat
{
    private $db;
    private $badger;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->badger = new badger();
    }

    // add a new user stat entry for a user
    public function add($userId)
    {
        try {
            $sql = "INSERT INTO user_stats (user_id) VALUES (:user_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("UserStat add error: " . $e->getMessage());
            return false;
        }
    }

    // increment a specific stat for a user
    public function increment($userId, $stat)
    {
        $allowedStats = ['vote_count', 'answer_count', 'ask_count', 'profile_view_count'];
        if (!in_array($stat, $allowedStats)) {
            throw new \InvalidArgumentException("Invalid stat type");
        }

        if ($stat === 'profile_view_count') {
            $sql = "SELECT profile_view_count FROM user_stats WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $result = $stmt->fetch();

            if ($result && $result['profile_view_count'] >= 49) {
                $this->badger->add($userId, 'celebrity');
            }
        }
        if ($stat === 'vote_count') {
            if ($_SESSION['vote_count'] >= 5) {
                $this->badger->add($userId, 'avid-voter');
            }
        }

        try {
            $sql = "UPDATE user_stats SET $stat = $stat + 1 WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("UserStat increment error: " . $e->getMessage());
            return false;
        }
    }

    // decrement a specific stat for a user
    public function decrement($userId, $stat)
    {
        $allowedStats = ['vote_count', 'answer_count', 'ask_count'];
        if (!in_array($stat, $allowedStats)) {
            throw new \InvalidArgumentException("Invalid stat type");
        }

        try {
            $sql = "UPDATE user_stats SET $stat = GREATEST($stat - 1, 0) WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("UserStat decrement error: " . $e->getMessage());
            return false;
        }
    }

    public function getProfileViews($userId)
    {
        try {
            $sql = "SELECT profile_view_count FROM user_stats WHERE user_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            $result = $stmt->fetch();

            return $result ? (int) $result['profile_view_count'] : 0;
        } catch (\PDOException $e) {
            error_log("UserStat getProfileViews error: " . $e->getMessage());
            return 0;
        }
    }
}