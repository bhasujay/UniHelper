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

    public function get_sessions_for_upcoming_window($lookahead_minutes)
    {
        // Get scheduled peer sessions that start within the next configured window (inclusive).
        $lookahead_minutes = max(1, (int) $lookahead_minutes);
        $current_time = date('Y-m-d H:i:s');
        $window_end_time = date('Y-m-d H:i:s', strtotime("+{$lookahead_minutes} minutes"));

        $sql = "SELECT id, title AS name, scheduled_at
            FROM peer_sessions
            WHERE status = 'scheduled'
              AND scheduled_at >= :current_time
              AND scheduled_at <= :window_end_time
            ORDER BY scheduled_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':current_time' => $current_time,
            ':window_end_time' => $window_end_time,
        ]);

        return $stmt->fetchAll();
    }

    public function get_subscribers_for_session($session_id)
    {
        // Get approved subscribers for a peer session.
        $sql = "SELECT u.email
            FROM users u
            INNER JOIN peer_session_subscriptions pss ON u.id = pss.subscriber_id
            WHERE pss.session_id = :session_id
              AND pss.status = 'approved'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':session_id' => (int) $session_id]);

        return $stmt->fetchAll();
    }
}