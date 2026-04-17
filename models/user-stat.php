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

        // Fetch current value from DB BEFORE incrementing, so badge thresholds are reliable
        $sql = "SELECT $stat FROM user_stats WHERE user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $currentValue = $row ? (int)$row[$stat] : 0;

        // Badge checks — compare against the current DB value (before the +1)
        if ($stat === 'profile_view_count') {
            // 'celebrity' badge at 50 profile views (check at 49 because we increment after)
            if ($currentValue === 49) {
                $this->badger->add($userId, 'celebrity');
            }
        }
        if ($stat === 'vote_count') {
            // 'avid-voter' badge at 5 votes cast (set to 4 for testing; change to 29 for production)
            if ($currentValue === 4) {
                $this->badger->add($userId, 'avid-voter');
            }
        }
        if ($stat === 'answer_count') {
            if ($currentValue === 0) { // 1st answer
                $this->badger->add($userId, 'community-member');
            }
            if ($currentValue === 4) { // 5th answer
                $this->badger->add($userId, 'social-worker');
            }
        }
        if ($stat === 'ask_count') {
            if ($currentValue === 0) { // 1st question
                $this->badger->add($userId, 'curious-mind');
            }
            if ($currentValue === 9) { // 10th question
                $this->badger->add($userId, 'regular-inquirer');
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