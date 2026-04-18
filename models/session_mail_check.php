<?php

namespace app\models;

use app\core\Database;

class session_mail_check
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function get_sessions_for_next_hour()
    {
        // Get sessions scheduled for today that start within the next hour (inclusive).
        $current_time = date('Y-m-d H:i:s');
        $next_hour_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $today_date = date('Y-m-d');

        $sql = "SELECT id, title AS name
            FROM sessions
            WHERE is_deleted = 0
              AND date = :today_date
              AND TIMESTAMP(date, time) >= :current_time
              AND TIMESTAMP(date, time) <= :next_hour_time
            ORDER BY time ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':today_date' => $today_date,
            ':current_time' => $current_time,
            ':next_hour_time' => $next_hour_time,
        ]);

        return $stmt->fetchAll();
    }

    public function get_subscribers_for_session($session_id)
    {
        // this function gets all the subscribers for a session, and return their emails
        $sql = "SELECT u.email FROM users u JOIN subscribers s ON u.id = s.Subscriber_ID WHERE s.Session_ID = :session_id AND s.status = 'approved'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':session_id' => (int) $session_id]);

        return $stmt->fetchAll();
    }
}