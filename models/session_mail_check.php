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
        // basically what this function does is to get all sessions that are starting in the next hour, and return their ids and names
        // 'deleted_at' is the actual time that the session is starting, so we can use it to filter the sessions that are starting in the next hour
        // 'duration' is the duration of the session, afte the session is starting, the 'deleted_at' will be updated to the time that the session is ending, so we can use it to filter the sessions that are starting in the next hour
        $current_time = date('Y-m-d H:i:s');
        $next_hour_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $sql = "SELECT id, name FROM sessions WHERE deleted_at > :current_time AND deleted_at <= :next_hour_time";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':current_time' => $current_time,
            ':next_hour_time' => $next_hour_time,
        ]);

        return $stmt->fetchAll();
    }

    public function get_subscribers_for_session($session_id)
    {
        // this function gets all the subscribers for a session, and return their emails
        $sql = "SELECT u.email FROM users u JOIN subscribers s ON u.id = s.Subscriber_ID WHERE s.Session_ID = :session_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':session_id' => (int) $session_id]);

        return $stmt->fetchAll();
    }
}