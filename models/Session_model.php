<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/models/base-model.php';
require_once dirname(__DIR__, 1) . '/models/notify.php';

use PDO;
use PDOException;
use Exception;

class Session_model extends BaseModel
{
    protected $table = 'peer_sessions';
    private $notifyModel;

    public function __construct()
    {
        parent::__construct();
        $this->notifyModel = new Notify();
    }

    // ───────────────────────────────────────────────────
    // READ — list endpoints
    // ───────────────────────────────────────────────────

    /**
     * All Sessions tab — visible, non-expired sessions for the viewer.
     * Public sessions: always visible.
     * University-only / private: visible if viewer shares the university OR is the author.
     */
    public function getAllSessions(int $viewerId, ?int $viewerUniId, int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    COALESCE((SELECT sub.status FROM peer_session_subscriptions sub WHERE sub.subscriber_id = :uid1 AND sub.session_id = s.id LIMIT 1), 'none') AS subscription_status
                FROM {$this->table} s
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE s.status IN ('scheduled','ongoing')
                  AND (
                        s.audience = 'public'
                        OR s.user_id = :uid2
                        OR (s.audience IN ('university_only','private') AND :vuni1 IS NOT NULL AND s.university_id = :vuni2)
                  )
                ORDER BY
                    CASE s.status WHEN 'ongoing' THEN 0 ELSE 1 END,
                    s.scheduled_at ASC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid1', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':vuni1', $viewerUniId, $viewerUniId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':vuni2', $viewerUniId, $viewerUniId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * My Sessions tab — all sessions authored by the user (including expired/cancelled).
     */
    public function getMySessions(int $userId, int $limit = 10, int $offset = 0): array
    {
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    'none' AS subscription_status
                FROM {$this->table} s
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE s.user_id = :uid
                ORDER BY s.scheduled_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Subscribed Sessions tab — sessions the user has a non-rejected subscription to.
     */
    public function getSubscribedSessions(int $userId): array
    {
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    sub.status AS subscription_status
                FROM peer_session_subscriptions sub
                INNER JOIN {$this->table} s ON s.id = sub.session_id
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE sub.subscriber_id = :uid
                  AND sub.status <> 'rejected'
                  AND s.status IN ('scheduled','ongoing')
                ORDER BY s.scheduled_at ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Single session with viewer-context subscription info.
     */
    public function getSessionById(int $id, int $viewerId): ?array
    {
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    COALESCE((SELECT sub.status FROM peer_session_subscriptions sub WHERE sub.subscriber_id = :uid1 AND sub.session_id = s.id LIMIT 1), 'none') AS subscription_status
                FROM {$this->table} s
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE s.id = :sid AND s.status <> 'cancelled'
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid1', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':sid', $id, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Backward-compatible search entry point.
     */
    public function searchSessions(string $query, int $viewerId, ?int $viewerUniId, string $tab, int $limit = 10, int $offset = 0): array
    {
        if ($tab === 'my-sessions') {
            return $this->searchMySessions($query, $viewerId, $limit, $offset);
        }
        if ($tab === 'subscribed-sessions') {
            return $this->searchSubscribedSessions($query, $viewerId, $limit, $offset);
        }
        return $this->searchAllSessions($query, $viewerId, $viewerUniId, $limit, $offset);
    }

    public function searchAllSessions(string $query, int $viewerId, ?int $viewerUniId, int $limit = 10, int $offset = 0): array
    {
        $like = '%' . $query . '%';
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    COALESCE((SELECT sub.status FROM peer_session_subscriptions sub WHERE sub.subscriber_id = :uid1 AND sub.session_id = s.id LIMIT 1), 'none') AS subscription_status
                FROM {$this->table} s
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE s.status IN ('scheduled','ongoing')
                  AND (
                        s.audience = 'public'
                        OR s.user_id = :uid2
                        OR (s.audience IN ('university_only','private') AND :vuni1 IS NOT NULL AND s.university_id = :vuni2)
                  )
                  AND (LOWER(s.title) LIKE LOWER(:q1) OR LOWER(s.description) LIKE LOWER(:q2) OR LOWER(COALESCE(s.tags,'')) LIKE LOWER(:q3) OR LOWER(COALESCE(m.name,'')) LIKE LOWER(:q4))
                ORDER BY s.scheduled_at ASC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid1', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':uid2', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':vuni1', $viewerUniId, $viewerUniId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':vuni2', $viewerUniId, $viewerUniId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q4', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchMySessions(string $query, int $viewerId, int $limit = 10, int $offset = 0): array
    {
        $like = '%' . $query . '%';
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    'none' AS subscription_status
                FROM {$this->table} s
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE s.user_id = :uid
                  AND (LOWER(s.title) LIKE LOWER(:q1) OR LOWER(s.description) LIKE LOWER(:q2) OR LOWER(COALESCE(s.tags,'')) LIKE LOWER(:q3) OR LOWER(COALESCE(m.name,'')) LIKE LOWER(:q4))
                ORDER BY s.scheduled_at DESC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $viewerId, PDO::PARAM_INT);
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q4', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchSubscribedSessions(string $query, int $userId, int $limit = 10, int $offset = 0): array
    {
        $like = '%' . $query . '%';
        $sql = "SELECT s.*, m.name AS major_name,
                    u.first_name, u.last_name, u.profile_picture,
                    uni.name AS university_name,
                    sub.status AS subscription_status
                FROM peer_session_subscriptions sub
                INNER JOIN {$this->table} s ON s.id = sub.session_id
                LEFT JOIN users u      ON s.user_id = u.id
                LEFT JOIN universities uni ON s.university_id = uni.id
                LEFT JOIN majors m     ON s.major_id = m.id
                WHERE sub.subscriber_id = :uid
                  AND sub.status <> 'rejected'
                  AND s.status IN ('scheduled','ongoing')
                  AND (LOWER(s.title) LIKE LOWER(:q1) OR LOWER(s.description) LIKE LOWER(:q2) OR LOWER(COALESCE(s.tags,'')) LIKE LOWER(:q3) OR LOWER(COALESCE(m.name,'')) LIKE LOWER(:q4))
                ORDER BY s.scheduled_at ASC
                LIMIT :lim OFFSET :off";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':uid', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q3', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q4', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':off', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ───────────────────────────────────────────────────
    // WRITE — session CRUD
    // ───────────────────────────────────────────────────

    public function createSession(array $data): int
    {
        $sql = "INSERT INTO {$this->table}
                    (user_id, title, description, major_id, university_id, audience, session_link, tags, scheduled_at, duration_minutes)
                VALUES
                    (:user_id, :title, :description, :major_id, :university_id, :audience, :session_link, :tags, :scheduled_at, :duration_minutes)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id'          => $data['user_id'],
            'title'            => $data['title'],
            'description'      => $data['description'],
            'major_id'         => $data['major_id'] ?: null,
            'university_id'    => $data['university_id'] ?: null,
            'audience'         => $data['audience'],
            'session_link'     => $data['session_link'] ?: null,
            'tags'             => $data['tags'] ?: null,
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => (int)($data['duration_minutes'] ?? 60),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateSession(int $id, int $ownerId, array $data): bool
    {
        $sql = "UPDATE {$this->table}
                SET title = :title, description = :description, major_id = :major_id,
                    audience = :audience, session_link = :session_link, tags = :tags,
                    scheduled_at = :scheduled_at, duration_minutes = :duration_minutes
                WHERE id = :id AND user_id = :owner_id AND status <> 'cancelled'";

        $stmt = $this->db->prepare($sql);
        $updated = $stmt->execute([
            'title'            => $data['title'],
            'description'      => $data['description'],
            'major_id'         => $data['major_id'] ?: null,
            'audience'         => $data['audience'],
            'session_link'     => $data['session_link'] ?: null,
            'tags'             => $data['tags'] ?: null,
            'scheduled_at'     => $data['scheduled_at'],
            'duration_minutes' => (int)($data['duration_minutes'] ?? 60),
            'id'               => $id,
            'owner_id'         => $ownerId,
        ]);

        if ($updated && $stmt->rowCount() > 0) {
            // Notify all active subscribers
            $subs = $this->getActiveSubscriberIds($id);
            foreach ($subs as $subId) {
                if ($subId === $ownerId) continue;
                $this->notify($subId, "The session \"{$data['title']}\" has been updated by the host.", $id);
            }
        }
        return $updated && $stmt->rowCount() > 0;
    }

    public function deleteSession(int $id, int $ownerId): bool
    {
        // Fetch title before cancelling
        $session = $this->getSessionById($id, $ownerId);
        if (!$session || (int)$session['user_id'] !== $ownerId) return false;

        $sql = "UPDATE {$this->table} SET status = 'cancelled' WHERE id = :id AND user_id = :owner_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id, 'owner_id' => $ownerId]);

        if ($stmt->rowCount() > 0) {
            $subs = $this->getActiveSubscriberIds($id);
            foreach ($subs as $subId) {
                if ($subId === $ownerId) continue;
                $this->notify($subId, "The session \"{$session['title']}\" has been cancelled by the host.", $id);
            }
            return true;
        }
        return false;
    }

    public function deleteCompletedSession(int $id, int $ownerId): bool
    {
        $pdo = $this->db->getConnection();

        $check = $this->db->prepare("SELECT id FROM {$this->table} WHERE id = :id AND user_id = :owner_id AND status IN ('completed','cancelled') LIMIT 1");
        $check->execute(['id' => $id, 'owner_id' => $ownerId]);
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            return false;
        }

        try {
            $pdo->beginTransaction();

            $this->db->prepare("DELETE FROM peer_session_subscriptions WHERE session_id = :sid")
                ->execute(['sid' => $id]);

            $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id AND user_id = :owner_id AND status IN ('completed','cancelled')");
            $stmt->execute(['id' => $id, 'owner_id' => $ownerId]);

            $pdo->commit();
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    // ───────────────────────────────────────────────────
    // SUBSCRIPTIONS
    // ───────────────────────────────────────────────────

    public function subscribe(int $userId, int $sessionId): array
    {
        $session = $this->getSessionById($sessionId, $userId);
        if (!$session) throw new Exception('Session not found.');
        if ((int)$session['user_id'] === $userId) throw new Exception('You cannot subscribe to your own session.');

        // University check for university_only and private
        if (in_array($session['audience'], ['university_only', 'private'], true)) {
            $subUni = $this->getUserUniversity($userId);
            $sesUni = $session['university_id'] ? (int)$session['university_id'] : null;
            if ($subUni === null || $sesUni === null || $subUni !== $sesUni) {
                throw new Exception('You can only subscribe to sessions from your university.');
            }
        }

        $targetStatus = ($session['audience'] === 'private') ? 'pending' : 'approved';

        // Check existing subscription
        $existing = $this->db->prepare("SELECT status FROM peer_session_subscriptions WHERE subscriber_id = :uid AND session_id = :sid LIMIT 1");
        $existing->execute(['uid' => $userId, 'sid' => $sessionId]);
        $row = $existing->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $update = $this->db->prepare("UPDATE peer_session_subscriptions SET status = :st, requested_at = NOW(), decided_at = NULL WHERE subscriber_id = :uid AND session_id = :sid");
            $update->execute(['st' => $targetStatus, 'uid' => $userId, 'sid' => $sessionId]);
        } else {
            $insert = $this->db->prepare("INSERT INTO peer_session_subscriptions (subscriber_id, session_id, status, requested_at, decided_at) VALUES (:uid, :sid, :st, NOW(), :da)");
            $insert->execute([
                'uid' => $userId,
                'sid' => $sessionId,
                'st'  => $targetStatus,
                'da'  => $targetStatus === 'approved' ? date('Y-m-d H:i:s') : null,
            ]);
        }

        $this->syncSubCount($sessionId);

        // Notifications
        $authorId = (int)$session['user_id'];
        if ($session['audience'] === 'private') {
            // Subscriber name for notification
            $subUser = $this->db->prepare("SELECT first_name, last_name FROM users WHERE id = :uid LIMIT 1");
            $subUser->execute(['uid' => $userId]);
            $u = $subUser->fetch(PDO::FETCH_ASSOC);
            $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
            $this->notify($authorId, "{$name} requested to join your private session \"{$session['title']}\".", $sessionId);
        } else {
            // For public/university_only: notify at milestone counts
            $count = $this->getSubCount($sessionId);
            if (in_array($count, [1, 5, 10, 50], true)) {
                $this->notify($authorId, "Your session \"{$session['title']}\" now has {$count} subscriber" . ($count > 1 ? 's' : '') . "!", $sessionId);
            }
        }

        return [
            'subscription_status' => $targetStatus,
            'sub_count'           => $this->getSubCount($sessionId),
        ];
    }

    public function unsubscribe(int $userId, int $sessionId): array
    {
        $stmt = $this->db->prepare("DELETE FROM peer_session_subscriptions WHERE subscriber_id = :uid AND session_id = :sid");
        $stmt->execute(['uid' => $userId, 'sid' => $sessionId]);
        $this->syncSubCount($sessionId);

        return [
            'subscription_status' => 'none',
            'sub_count'           => $this->getSubCount($sessionId),
        ];
    }

    /**
     * Get subscriber list for a private session (owner only).
     */
    public function getSubscribers(int $sessionId, int $ownerId): array
    {
        $sql = "SELECT sub.subscriber_id, sub.status, sub.requested_at, sub.decided_at,
                    u.first_name, u.last_name, u.profile_picture
                FROM peer_session_subscriptions sub
                INNER JOIN users u ON u.id = sub.subscriber_id
                INNER JOIN {$this->table} s ON s.id = sub.session_id
                WHERE sub.session_id = :sid AND s.user_id = :oid AND s.status <> 'cancelled'
                ORDER BY FIELD(sub.status, 'pending', 'approved', 'rejected'), u.first_name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sid' => $sessionId, 'oid' => $ownerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Approve or reject a subscriber (owner of session only).
     */
    public function updateSubscriptionStatus(int $sessionId, int $ownerId, int $subscriberId, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'], true)) return false;

        // Validate ownership
        $check = $this->db->prepare("SELECT id, title FROM {$this->table} WHERE id = :sid AND user_id = :oid AND status <> 'cancelled' LIMIT 1");
        $check->execute(['sid' => $sessionId, 'oid' => $ownerId]);
        $session = $check->fetch(PDO::FETCH_ASSOC);
        if (!$session) return false;

        $stmt = $this->db->prepare("UPDATE peer_session_subscriptions SET status = :st, decided_at = NOW() WHERE subscriber_id = :subid AND session_id = :sid");
        $stmt->execute(['st' => $status, 'subid' => $subscriberId, 'sid' => $sessionId]);

        if ($stmt->rowCount() > 0) {
            $this->syncSubCount($sessionId);

            if ($status === 'approved') {
                $this->notify($subscriberId, "Your request to join \"{$session['title']}\" has been approved!", $sessionId);
            } else {
                $this->notify($subscriberId, "Your request to join \"{$session['title']}\" was declined.", $sessionId);
            }
            return true;
        }
        return false;
    }

    // ───────────────────────────────────────────────────
    // MAJORS (for the create form dropdown)
    // ───────────────────────────────────────────────────

    public function getMajors(): array
    {
        $stmt = $this->db->prepare("SELECT id, name FROM majors ORDER BY name ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function searchMajors(string $query, int $limit = 12): array
    {
        $like = '%' . $query . '%';
        $sql = "SELECT id, name FROM majors WHERE LOWER(name) LIKE LOWER(:q) ORDER BY name ASC LIMIT :lim";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':q', $like, PDO::PARAM_STR);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function majorExists(int $majorId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM majors WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $majorId]);
        return (bool)$stmt->fetchColumn();
    }

    // ───────────────────────────────────────────────────
    // LIFECYCLE — auto-update status based on time
    // ───────────────────────────────────────────────────

    public function autoUpdateStatuses(): void
    {
        $now = date('Y-m-d H:i:s');

        // scheduled → ongoing (past scheduled_at but within duration)
        $this->db->prepare(
            "UPDATE {$this->table} SET status = 'ongoing'
             WHERE status = 'scheduled' AND scheduled_at <= :now1
               AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) > :now2"
        )->execute(['now1' => $now, 'now2' => $now]);

        // ongoing/scheduled → completed (past end time)
        $this->db->prepare(
            "UPDATE {$this->table} SET status = 'completed'
             WHERE status IN ('scheduled','ongoing')
               AND DATE_ADD(scheduled_at, INTERVAL duration_minutes MINUTE) <= :now"
        )->execute(['now' => $now]);
    }

    // ───────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ───────────────────────────────────────────────────

    private function syncSubCount(int $sessionId): void
    {
        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET sub_count = (
                SELECT COUNT(*) FROM peer_session_subscriptions WHERE session_id = :sid AND status <> 'rejected'
            ) WHERE id = :sid2"
        );
        $stmt->execute(['sid' => $sessionId, 'sid2' => $sessionId]);
    }

    private function getSubCount(int $sessionId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM peer_session_subscriptions WHERE session_id = :sid AND status <> 'rejected'");
        $stmt->execute(['sid' => $sessionId]);
        return (int)$stmt->fetchColumn();
    }

    private function getActiveSubscriberIds(int $sessionId): array
    {
        $stmt = $this->db->prepare("SELECT subscriber_id FROM peer_session_subscriptions WHERE session_id = :sid AND status <> 'rejected'");
        $stmt->execute(['sid' => $sessionId]);
        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    private function getUserUniversity(int $userId): ?int
    {
        $stmt = $this->db->prepare("SELECT university FROM users WHERE id = :uid LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($row && $row['university']) ? (int)$row['university'] : null;
    }

    private function notify(int $recipientId, string $message, int $sessionId): void
    {
        if ($recipientId <= 0) return;
        try {
            $link = '/UniHelper/peer-learning?session=' . $sessionId;
            $this->notifyModel->insertNotification($recipientId, $message, 'session', $link);
        } catch (\Throwable $e) {
            // Notification must not block session workflows
        }
    }
}
