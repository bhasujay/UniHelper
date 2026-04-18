<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/models/base-model.php';
require_once dirname(__DIR__, 1) . '/models/notify.php';

use PDO;
use PDOException;
use Exception;

class Session_model extends BaseModel {
    protected $table = 'sessions';
    private $notifyModel;

    private const SUB_STATUS_NONE = 'none';
    private const SUB_STATUS_PENDING = 'pending';
    private const SUB_STATUS_APPROVED = 'approved';
    private const SUB_STATUS_REJECTED = 'rejected';
    private const DEFAULT_DURATION_HOURS = 1.0;

    public function __construct()
    {
        parent::__construct();
        $this->notifyModel = new Notify();
    }
    
    /**
     * Get all sessions excluding soft-deleted and manually deleted ones
     */
    public function findAll($conditions = [], $limit = null, $offset = null, $currentUserId = null, $currentUserUniversity = null) {
        $currentUserIdValue = (int)$currentUserId;
        $viewerUniversity = $this->normalizeUniversity($currentUserUniversity);
        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university, 
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.deleted_at IS NULL
                  AND s.is_deleted = 0
                  AND (
                        s.user_id = :uid4
                        OR s.audience = 'all_universities'
                        OR (
                            :viewer_university1 IS NOT NULL
                            AND s.university = :viewer_university2
                            AND s.audience IN ('my_university', 'private')
                        )
                  )";
        $params = [
            'uid1' => $currentUserIdValue,
            'uid2' => $currentUserIdValue,
            'uid3' => $currentUserIdValue,
            'uid4' => $currentUserIdValue,
            'viewer_university1' => $viewerUniversity,
            'viewer_university2' => $viewerUniversity
        ];
        
        if (!empty($conditions)) {
            foreach ($conditions as $column => $value) {
                $paramKey = 'cond_' . $column;
                $sql .= " AND s.{$column} = :{$paramKey}";
                $params[$paramKey] = $value;
            }
        }
        
        $sql .= " ORDER BY s.date DESC, s.time DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch sessions: " . $e->getMessage());
        }
    }
    
    /**
     * Get single session by ID excluding manually deleted
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id AND is_deleted = 0";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find session: " . $e->getMessage());
        }
    }

    /**
     * Get one visible session with viewer-specific fields for modal display.
     */
    public function findVisibleById($sessionId, $currentUserId, $currentUserUniversity = null) {
        $currentUserIdValue = (int)$currentUserId;
        $viewerUniversity = $this->normalizeUniversity($currentUserUniversity);

        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university,
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN universities uni ON u.university = uni.id
            WHERE s.id = :session_id
              AND s.is_deleted = 0
              AND (
                    s.user_id = :uid4
                    OR (
                        s.deleted_at IS NULL
                        AND (
                            s.audience = 'all_universities'
                            OR (
                                :viewer_university1 IS NOT NULL
                                AND s.university = :viewer_university2
                                AND s.audience IN ('my_university', 'private')
                            )
                        )
                    )
                )
            LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'session_id' => (int)$sessionId,
                'uid1' => $currentUserIdValue,
                'uid2' => $currentUserIdValue,
                'uid3' => $currentUserIdValue,
                'uid4' => $currentUserIdValue,
                'viewer_university1' => $viewerUniversity,
                'viewer_university2' => $viewerUniversity
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch session for view: " . $e->getMessage());
        }
    }
    
    /**
     * Get sessions created by a specific user (includes expired, excludes manually deleted)
     */
    public function findByUserId($userId, $limit = null, $offset = null, $currentUserId = null) {
        $currentUserIdValue = (int)$currentUserId;
        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university, 
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.user_id = :user_id AND s.is_deleted = 0 
                ORDER BY s.date DESC, s.time DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'uid1' => $currentUserIdValue,
                'uid2' => $currentUserIdValue,
                'uid3' => $currentUserIdValue
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find sessions by user ID: " . $e->getMessage());
        }
    }
    
    /**
     * Get sessions by university (excludes expired and manually deleted)
     */
    public function findByUniversity($university, $limit = null, $offset = null, $currentUserId = null) {
        $currentUserIdValue = (int)$currentUserId;
        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university, 
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.university = :university
                  AND s.deleted_at IS NULL
                  AND s.is_deleted = 0
                  AND s.audience IN ('my_university', 'private')
                ORDER BY s.date DESC, s.time DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'university' => $university,
                'uid1' => $currentUserIdValue,
                'uid2' => $currentUserIdValue,
                'uid3' => $currentUserIdValue
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find sessions by university: " . $e->getMessage());
        }
    }
    
    /**
     * Get sessions by audience type (excludes expired and manually deleted)
     * $audience can be 'my_university', 'all_universities' or 'private'
     */
    public function findByAudience($audience, $userUniversity = null, $limit = null, $offset = null, $currentUserId = null) {
        if (($audience === 'my_university' || $audience === 'private') && $userUniversity) {
            return $this->findByUniversity($userUniversity, $limit, $offset, $currentUserId);
        } elseif ($audience === 'all_universities') {
            return $this->findAll([], $limit, $offset, $currentUserId, $userUniversity);
        }
        return [];
    }
    
    /**
     * Get sessions by subject (excludes expired and manually deleted)
     */
    public function findBySubject($subject, $limit = null, $offset = null, $currentUserId = null, $currentUserUniversity = null) {
        $currentUserIdValue = (int)$currentUserId;
        $viewerUniversity = $this->normalizeUniversity($currentUserUniversity);
        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university, 
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.subject = :subject
                  AND s.deleted_at IS NULL
                  AND s.is_deleted = 0
                  AND (
                        s.user_id = :uid4
                        OR s.audience = 'all_universities'
                        OR (
                            :viewer_university1 IS NOT NULL
                            AND s.university = :viewer_university2
                            AND s.audience IN ('my_university', 'private')
                        )
                  )
                ORDER BY s.date DESC, s.time DESC";
        
        if ($limit) {
            $sql .= " LIMIT {$limit}";
            if ($offset) {
                $sql .= " OFFSET {$offset}";
            }
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'subject' => $subject,
                'uid1' => $currentUserIdValue,
                'uid2' => $currentUserIdValue,
                'uid3' => $currentUserIdValue,
                'uid4' => $currentUserIdValue,
                'viewer_university1' => $viewerUniversity,
                'viewer_university2' => $viewerUniversity
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find sessions by subject: " . $e->getMessage());
        }
    }

    /**
     * Search sessions created by the owner (My Sessions tab) while hiding expired entries.
     */
    public function searchMySessions($query, $ownerId, $limit = 10, $offset = 0, $currentUserId = null) {
        $queryValue = trim((string)$query);
        if ($queryValue === '') {
            return [];
        }

        $currentUserIdValue = (int)$currentUserId;
        $safeLimit = max(1, (int)$limit);
        $safeOffset = max(0, (int)$offset);
        $activeWindowSql = $this->getSessionActiveWindowConditionSql('s');

        // Each named placeholder must appear exactly once (ATTR_EMULATE_PREPARES = false).
        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university,
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN universities uni ON u.university = uni.id
            WHERE s.user_id = :owner_id
              AND s.is_deleted = 0
              AND s.deleted_at IS NULL
                            AND {$activeWindowSql}
              AND (
                    LOWER(s.title) LIKE LOWER(:lq1)
                    OR LOWER(s.subject) LIKE LOWER(:lq2)
                    OR LOWER(s.description) LIKE LOWER(:lq3)
                    OR LOWER(COALESCE(s.tags, '')) LIKE LOWER(:lq4)
                    OR LOWER(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) LIKE LOWER(:lq5)
              )
            ORDER BY
                CASE
                    WHEN LOWER(s.title) = LOWER(:eq1) THEN 0
                    WHEN LOWER(s.title) LIKE LOWER(:pq1) THEN 1
                    WHEN LOWER(s.title) LIKE LOWER(:lq6) THEN 2
                    WHEN LOWER(s.subject) = LOWER(:eq2) THEN 3
                    WHEN LOWER(s.subject) LIKE LOWER(:pq2) THEN 4
                    WHEN LOWER(s.subject) LIKE LOWER(:lq7) THEN 5
                    WHEN LOWER(s.description) LIKE LOWER(:lq8) THEN 6
                    WHEN LOWER(COALESCE(s.tags, '')) LIKE LOWER(:lq9) THEN 7
                    WHEN LOWER(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) LIKE LOWER(:lq10) THEN 8
                    ELSE 9
                END,
                s.date ASC,
                s.time ASC
            LIMIT {$safeLimit} OFFSET {$safeOffset}";

        $likeVal   = '%' . $queryValue . '%';
        $prefixVal = $queryValue . '%';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uid1'     => $currentUserIdValue,
                'uid2'     => $currentUserIdValue,
                'uid3'     => $currentUserIdValue,
                'owner_id' => (int)$ownerId,
                // WHERE clause
                'lq1'  => $likeVal,
                'lq2'  => $likeVal,
                'lq3'  => $likeVal,
                'lq4'  => $likeVal,
                'lq5'  => $likeVal,
                // ORDER BY clause
                'eq1'  => $queryValue,
                'pq1'  => $prefixVal,
                'lq6'  => $likeVal,
                'eq2'  => $queryValue,
                'pq2'  => $prefixVal,
                'lq7'  => $likeVal,
                'lq8'  => $likeVal,
                'lq9'  => $likeVal,
                'lq10' => $likeVal,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to search your sessions: " . $e->getMessage());
        }
    }

    /**
     * Search visible sessions for the viewer (All Sessions tab) while hiding expired entries.
     */
    public function searchVisibleSessions($query, $currentUserId, $currentUserUniversity = null, $limit = 10, $offset = 0) {
        $queryValue = trim((string)$query);
        if ($queryValue === '') {
            return [];
        }

        $currentUserIdValue = (int)$currentUserId;
        $viewerUniversity = $this->normalizeUniversity($currentUserUniversity);
        $safeLimit = max(1, (int)$limit);
        $safeOffset = max(0, (int)$offset);
        $activeWindowSql = $this->getSessionActiveWindowConditionSql('s');

        // Each named placeholder must appear exactly once (ATTR_EMULATE_PREPARES = false).
        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university,
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN universities uni ON u.university = uni.id
            WHERE s.deleted_at IS NULL
              AND s.is_deleted = 0
                            AND {$activeWindowSql}
              AND (
                    s.user_id = :uid4
                    OR s.audience = 'all_universities'
                    OR (
                        :viewer_university1 IS NOT NULL
                        AND s.university = :viewer_university2
                        AND s.audience IN ('my_university', 'private')
                    )
              )
              AND (
                    LOWER(s.title) LIKE LOWER(:lq1)
                    OR LOWER(s.subject) LIKE LOWER(:lq2)
                    OR LOWER(s.description) LIKE LOWER(:lq3)
                    OR LOWER(COALESCE(s.tags, '')) LIKE LOWER(:lq4)
                    OR LOWER(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) LIKE LOWER(:lq5)
              )
            ORDER BY
                CASE
                    WHEN LOWER(s.title) = LOWER(:eq1) THEN 0
                    WHEN LOWER(s.title) LIKE LOWER(:pq1) THEN 1
                    WHEN LOWER(s.title) LIKE LOWER(:lq6) THEN 2
                    WHEN LOWER(s.subject) = LOWER(:eq2) THEN 3
                    WHEN LOWER(s.subject) LIKE LOWER(:pq2) THEN 4
                    WHEN LOWER(s.subject) LIKE LOWER(:lq7) THEN 5
                    WHEN LOWER(s.description) LIKE LOWER(:lq8) THEN 6
                    WHEN LOWER(COALESCE(s.tags, '')) LIKE LOWER(:lq9) THEN 7
                    WHEN LOWER(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) LIKE LOWER(:lq10) THEN 8
                    ELSE 9
                END,
                s.date ASC,
                s.time ASC
            LIMIT {$safeLimit} OFFSET {$safeOffset}";

        $likeVal   = '%' . $queryValue . '%';
        $prefixVal = $queryValue . '%';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uid1'              => $currentUserIdValue,
                'uid2'              => $currentUserIdValue,
                'uid3'              => $currentUserIdValue,
                'uid4'              => $currentUserIdValue,
                'viewer_university1' => $viewerUniversity,
                'viewer_university2' => $viewerUniversity,
                // WHERE clause
                'lq1'  => $likeVal,
                'lq2'  => $likeVal,
                'lq3'  => $likeVal,
                'lq4'  => $likeVal,
                'lq5'  => $likeVal,
                // ORDER BY clause
                'eq1'  => $queryValue,
                'pq1'  => $prefixVal,
                'lq6'  => $likeVal,
                'eq2'  => $queryValue,
                'pq2'  => $prefixVal,
                'lq7'  => $likeVal,
                'lq8'  => $likeVal,
                'lq9'  => $likeVal,
                'lq10' => $likeVal,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to search sessions: " . $e->getMessage());
        }
    }

    /**
     * Subscribe a user to a session
     */
    public function subscribe($userId, $sessionId) {
        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();

                                                $sessionSql = "SELECT id, user_id, title, audience, university
                           FROM {$this->table}
                           WHERE id = :session_id
                             AND is_deleted = 0
                             AND deleted_at IS NULL
                           LIMIT 1
                           FOR UPDATE";
            $sessionStmt = $this->db->prepare($sessionSql);
            $sessionStmt->execute(['session_id' => $sessionId]);
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception("Session not found or unavailable.");
            }

            if (in_array($session['audience'], ['my_university', 'private'], true)) {
                $subscriberUniversity = $this->getUserUniversity((int)$userId);
                $sessionUniversity = isset($session['university']) ? (int)$session['university'] : null;

                if ($subscriberUniversity === null || $sessionUniversity === null || $subscriberUniversity !== $sessionUniversity) {
                    throw new Exception('You can only subscribe to sessions from your university.');
                }
            }

            $targetStatus = ($session['audience'] === 'private')
                ? self::SUB_STATUS_PENDING
                : self::SUB_STATUS_APPROVED;

            $existingSql = "SELECT status
                            FROM subscribers
                            WHERE Subscriber_ID = :subscriber_id
                              AND Session_ID = :session_id
                            LIMIT 1
                            FOR UPDATE";
            $existingStmt = $this->db->prepare($existingSql);
            $existingStmt->execute([
                'subscriber_id' => $userId,
                'session_id' => $sessionId
            ]);
            $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

            if ($existing) {
                $currentStatus = $existing['status'] ?? self::SUB_STATUS_APPROVED;
                if ($currentStatus !== $targetStatus) {
                    if ($targetStatus === self::SUB_STATUS_PENDING) {
                        $updateSql = "UPDATE subscribers
                                      SET status = :status,
                                          requested_at = NOW(),
                                          approved_at = NULL,
                                          rejected_at = NULL
                                      WHERE Subscriber_ID = :subscriber_id
                                        AND Session_ID = :session_id";
                    } else {
                        $updateSql = "UPDATE subscribers
                                      SET status = :status,
                                          approved_at = NOW(),
                                          rejected_at = NULL
                                      WHERE Subscriber_ID = :subscriber_id
                                        AND Session_ID = :session_id";
                    }
                    $updateStmt = $this->db->prepare($updateSql);
                    $updateStmt->execute([
                        'status' => $targetStatus,
                        'subscriber_id' => $userId,
                        'session_id' => $sessionId
                    ]);
                }
            } else {
                $insertSql = "INSERT INTO subscribers (
                                Subscriber_ID,
                                Session_ID,
                                status,
                                requested_at,
                                approved_at,
                                rejected_at
                              ) VALUES (
                                :subscriber_id,
                                :session_id,
                                :status,
                                NOW(),
                                :approved_at,
                                NULL
                              )";
                $insertStmt = $this->db->prepare($insertSql);
                $insertStmt->execute([
                    'subscriber_id' => $userId,
                    'session_id' => $sessionId,
                    'status' => $targetStatus,
                    'approved_at' => $targetStatus === self::SUB_STATUS_APPROVED ? date('Y-m-d H:i:s') : null
                ]);
            }

            $this->syncSubCount($sessionId);
            $state = $this->getSubscriptionState($userId, $sessionId, (string)$session['audience']);
            $state['sub_count'] = $this->getSubCount($sessionId);

            if ((string)$session['audience'] === 'private' && (int)$session['user_id'] !== (int)$userId) {
                $this->safeInsertSessionNotification(
                    (int)$session['user_id'],
                    'A user has subscribed to your private session.',
                    $this->buildSessionDeepLink((int)$sessionId, 'my-sessions')
                );
            }

            $pdo->commit();
            return $state;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Failed to subscribe to session: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Unsubscribe a user from a session
     */
    public function unsubscribe($userId, $sessionId) {
        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();
            $sessionSql = "SELECT id, audience
                           FROM {$this->table}
                           WHERE id = :session_id
                             AND is_deleted = 0
                           LIMIT 1
                           FOR UPDATE";
            $sessionStmt = $this->db->prepare($sessionSql);
            $sessionStmt->execute(['session_id' => $sessionId]);
            $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

            if (!$session) {
                throw new Exception("Session not found.");
            }

            $sql = "DELETE FROM subscribers
                    WHERE Subscriber_ID = :subscriber_id AND Session_ID = :session_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'subscriber_id' => $userId,
                'session_id' => $sessionId
            ]);

            $this->syncSubCount($sessionId);

            $state = [
                'subscription_status' => self::SUB_STATUS_NONE,
                'is_subscribed' => 0,
                'can_join' => ($session['audience'] === 'private') ? 0 : 1,
                'sub_count' => $this->getSubCount($sessionId)
            ];

            $pdo->commit();
            return $state;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Failed to unsubscribe from session: " . $e->getMessage());
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Get subscriber list for an owner-managed private session.
     */
    public function getSubscriberList($sessionId, $ownerId) {
        $sql = "SELECT
                    sub.Subscriber_ID AS subscriber_id,
                    u.first_name,
                    u.last_name,
                    sub.status,
                    sub.requested_at,
                    sub.approved_at,
                    sub.rejected_at
                FROM subscribers sub
                INNER JOIN users u ON u.id = sub.Subscriber_ID
                INNER JOIN {$this->table} s ON s.id = sub.Session_ID
                WHERE sub.Session_ID = :session_id
                  AND s.user_id = :owner_id
                  AND s.is_deleted = 0
                  AND s.audience = 'private'
                ORDER BY FIELD(sub.status, 'pending', 'approved', 'rejected'), u.first_name, u.last_name";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'session_id' => $sessionId,
                'owner_id' => $ownerId
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch subscriber list: " . $e->getMessage());
        }
    }

    /**
     * Approve or reject a subscriber for a private session owned by the requester.
     */
    public function updateSubscriberStatus($sessionId, $ownerId, $subscriberId, $status) {
        if (!in_array($status, [self::SUB_STATUS_APPROVED, self::SUB_STATUS_REJECTED], true)) {
            throw new Exception('Invalid subscriber status.');
        }

        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();

            $sessionSql = "SELECT id
                           FROM {$this->table}
                           WHERE id = :session_id
                             AND user_id = :owner_id
                             AND is_deleted = 0
                             AND audience = 'private'
                           LIMIT 1
                           FOR UPDATE";
            $sessionStmt = $this->db->prepare($sessionSql);
            $sessionStmt->execute([
                'session_id' => $sessionId,
                'owner_id' => $ownerId
            ]);

            if (!$sessionStmt->fetch(PDO::FETCH_ASSOC)) {
                $pdo->rollBack();
                return false;
            }

            $subscriberSql = "SELECT status
                              FROM subscribers
                              WHERE Session_ID = :session_id
                                AND Subscriber_ID = :subscriber_id
                              LIMIT 1
                              FOR UPDATE";
            $subscriberStmt = $this->db->prepare($subscriberSql);
            $subscriberStmt->execute([
                'session_id' => $sessionId,
                'subscriber_id' => $subscriberId
            ]);
            $existing = $subscriberStmt->fetch(PDO::FETCH_ASSOC);

            if (!$existing) {
                $pdo->rollBack();
                return false;
            }

            if ($status === self::SUB_STATUS_APPROVED) {
                $updateSql = "UPDATE subscribers
                              SET status = :status,
                                  approved_at = NOW(),
                                  rejected_at = NULL
                              WHERE Session_ID = :session_id
                                AND Subscriber_ID = :subscriber_id";
            } else {
                $updateSql = "UPDATE subscribers
                              SET status = :status,
                                  rejected_at = NOW(),
                                  approved_at = NULL
                              WHERE Session_ID = :session_id
                                AND Subscriber_ID = :subscriber_id";
            }

            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([
                'status' => $status,
                'session_id' => $sessionId,
                'subscriber_id' => $subscriberId
            ]);

            $this->syncSubCount($sessionId);
            $subCount = $this->getSubCount($sessionId);

            if ($status === self::SUB_STATUS_APPROVED && (int)$subscriberId !== (int)$ownerId) {
                $this->safeInsertSessionNotification(
                    (int)$subscriberId,
                    'Your private session subscription was approved.',
                    $this->buildSessionDeepLink((int)$sessionId, 'all-sessions')
                );
            }

            $pdo->commit();

            return [
                'status' => $status,
                'sub_count' => $subCount
            ];
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Failed to update subscriber status: " . $e->getMessage());
        }
    }

    /**
     * Get current subscriber count excluding rejected rows.
     */
    public function getSubCount($sessionId): int
    {
        $sql = "SELECT COUNT(*)
                FROM subscribers
                WHERE Session_ID = :session_id
                  AND status <> 'rejected'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['session_id' => $sessionId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Failed to count subscribers: " . $e->getMessage());
        }
    }

    /**
     * Verify a session belongs to owner and is private.
     */
    public function isPrivateSessionOwnedBy($sessionId, $ownerId): bool
    {
        $sql = "SELECT COUNT(*)
                FROM {$this->table}
                WHERE id = :session_id
                  AND user_id = :owner_id
                  AND is_deleted = 0
                  AND audience = 'private'";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'session_id' => $sessionId,
                'owner_id' => $ownerId
            ]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to verify private session ownership: " . $e->getMessage());
        }
    }
    
    /**
     * Mark session as expired (soft delete but not manually deleted)
     */
    public function markAsExpired($id) {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW() WHERE id = :id AND is_deleted = 0";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to mark session as expired: " . $e->getMessage());
        }
    }
    
    /**
     * Soft delete a session (user initiated delete)
     */
    public function softDelete($id) {
        $sql = "UPDATE {$this->table} SET deleted_at = NOW(), is_deleted = 1 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete session: " . $e->getMessage());
        }
    }

    /**
     * Update a session owned by a specific user
     */
    public function updateByOwner($id, $userId, $data) {
        $sql = "UPDATE {$this->table}
                SET title = :title,
                    subject = :subject,
                    description = :description,
                    date = :date,
                    time = :time,
                    duration = :duration,
                    session_link = :session_link,
                    audience = :audience,
                    tags = :tags
                WHERE id = :id AND user_id = :user_id AND is_deleted = 0";

        $params = [
            'id' => $id,
            'user_id' => $userId,
            'title' => $data['title'],
            'subject' => $data['subject'],
            'description' => $data['description'],
            'date' => $data['date'],
            'time' => $data['time'],
            'duration' => $data['duration'],
            'session_link' => $data['session_link'],
            'audience' => $data['audience'],
            'tags' => $data['tags']
        ];

        try {
            $stmt = $this->db->prepare($sql);
            $updated = $stmt->execute($params);

            if ($updated && $stmt->rowCount() > 0) {
                $subscriberIds = $this->getNonRejectedSubscriberIds((int)$id);
                foreach ($subscriberIds as $subscriberId) {
                    if ((int)$subscriberId === (int)$userId) {
                        continue;
                    }

                    $this->safeInsertSessionNotification(
                        (int)$subscriberId,
                        'A session you subscribed to has been updated by the author.',
                        $this->buildSessionDeepLink((int)$id, 'all-sessions')
                    );
                }
            }

            return $updated;
        } catch (PDOException $e) {
            throw new Exception("Failed to update session: " . $e->getMessage());
        }
    }

    public function notifyDeletedSessionToSubscribers(int $sessionId, int $ownerId): void
    {
        $subscriberIds = $this->getNonRejectedSubscriberIds($sessionId);

        foreach ($subscriberIds as $subscriberId) {
            if ((int)$subscriberId === (int)$ownerId) {
                continue;
            }

            $this->safeInsertSessionNotification(
                (int)$subscriberId,
                'A session you subscribed to has been deleted by the author.',
                $this->buildSessionDeepLink($sessionId, 'all-sessions')
            );
        }
    }
    
    /**
     * Restore a soft-deleted session
     */
    public function restore($id) {
        $sql = "UPDATE {$this->table} SET deleted_at = NULL, is_deleted = 0 WHERE id = :id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $id]);
        } catch (PDOException $e) {
            throw new Exception("Failed to restore session: " . $e->getMessage());
        }
    }
    
    /**
     * Count non-deleted sessions
     */
    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE is_deleted = 0";
        $params = [];
        
        if (!empty($conditions)) {
            foreach ($conditions as $column => $value) {
                $sql .= " AND {$column} = :{$column}";
                $params[$column] = $value;
            }
        }
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Failed to count sessions: " . $e->getMessage());
        }
    }
    
    /**
     * Check if session exists and is not manually deleted
     */
    public function exists($id) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE id = :id AND is_deleted = 0";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $id]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to check session existence: " . $e->getMessage());
        }
    }

    private function getSessionActiveWindowConditionSql(string $alias): string
    {
        return 'NOW() <= ' . $this->getSessionEndDateTimeSql($alias);
    }

    private function getSessionEndDateTimeSql(string $alias): string
    {
        $defaultDuration = (float)self::DEFAULT_DURATION_HOURS;
        $durationHoursSql = "CASE
                WHEN CAST(COALESCE({$alias}.duration, 0) AS DECIMAL(10,2)) > 0
                THEN CAST({$alias}.duration AS DECIMAL(10,2))
                ELSE {$defaultDuration}
            END";

        return "DATE_ADD(
                TIMESTAMP({$alias}.date, COALESCE({$alias}.time, '00:00:00')),
                INTERVAL ({$durationHoursSql} * 3600) SECOND
            )";
    }

    private function syncSubCount($sessionId): void
    {
        $sql = "UPDATE {$this->table} s
                SET s.sub_count = (
                    SELECT COUNT(*)
                    FROM subscribers sub
                    WHERE sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                )
                WHERE s.id = :session_id
                  AND s.is_deleted = 0";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['session_id' => $sessionId]);
    }

    private function getNonRejectedSubscriberIds(int $sessionId): array
    {
        $sql = "SELECT Subscriber_ID
                FROM subscribers
                WHERE Session_ID = :session_id
                  AND status <> 'rejected'";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['session_id' => $sessionId]);

        $subscriberIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return array_map('intval', $subscriberIds ?: []);
    }

    private function buildSessionDeepLink(int $sessionId, string $tab): string
    {
        $safeTab = $tab === 'my-sessions' ? 'my-sessions' : 'all-sessions';
        return '/UniHelper/peer-learning?session_id=' . $sessionId . '&tab=' . $safeTab;
    }

    private function safeInsertSessionNotification(int $recipientId, string $message, string $link): void
    {
        if ($recipientId <= 0) {
            return;
        }

        try {
            $this->notifyModel->insertNotification($recipientId, $message, 'session', $link);
        } catch (\Throwable $e) {
            // Notification publishing must not block session workflows.
        }
    }

    private function getSubscriptionState($userId, $sessionId, string $audience): array
    {
        $sql = "SELECT status
                FROM subscribers
                WHERE Subscriber_ID = :subscriber_id
                  AND Session_ID = :session_id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'subscriber_id' => $userId,
            'session_id' => $sessionId
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $status = $row['status'] ?? self::SUB_STATUS_NONE;

        if ($status === self::SUB_STATUS_REJECTED) {
            $status = self::SUB_STATUS_NONE;
        }

        $isSubscribed = in_array($status, [self::SUB_STATUS_PENDING, self::SUB_STATUS_APPROVED], true) ? 1 : 0;
        $canJoin = ($audience === 'private')
            ? (($status === self::SUB_STATUS_APPROVED) ? 1 : 0)
            : 1;

        return [
            'subscription_status' => $status,
            'is_subscribed' => $isSubscribed,
            'can_join' => $canJoin
        ];
    }

    private function normalizeUniversity($university)
    {
        if ($university === null || $university === '') {
            return null;
        }

        return (int)$university;
    }

    private function getUserUniversity(int $userId): ?int
    {
        $sql = "SELECT university FROM users WHERE id = :user_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || $row['university'] === null || $row['university'] === '') {
            return null;
        }

        return (int)$row['university'];
    }

    // get subscribes sessions for a userid
    public function findSubscribedSessions(int $userId): array
    {
        $currentUserIdValue = (int)$userId;
        $viewerUniversity = $this->normalizeUniversity($this->getUserUniversity($currentUserIdValue));

        $sql = "SELECT s.*, u.first_name as creator_first_name, u.last_name as creator_last_name,
            u.profile_picture as creator_profile_picture, uni.name as creator_university,
                COALESCE((
                    SELECT sub.status
                    FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid1 AND sub.Session_ID = s.id
                    LIMIT 1
                ), 'none') AS subscription_status,
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :uid2
                      AND sub.Session_ID = s.id
                      AND sub.status <> 'rejected'
                ) THEN 1 ELSE 0 END AS is_subscribed,
                CASE WHEN s.audience = 'private' THEN
                    CASE WHEN EXISTS (
                        SELECT 1 FROM subscribers sub
                        WHERE sub.Subscriber_ID = :uid3
                          AND sub.Session_ID = s.id
                          AND sub.status = 'approved'
                    ) THEN 1 ELSE 0 END
                ELSE 1 END AS can_join
            FROM {$this->table} s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN universities uni ON u.university = uni.id
            WHERE s.deleted_at IS NULL
              AND s.is_deleted = 0
              AND (
                    s.user_id = :uid4
                    OR s.audience = 'all_universities'
                    OR (
                        :viewer_university1 IS NOT NULL
                        AND s.university = :viewer_university2
                        AND s.audience IN ('my_university', 'private')
                    )
              )
              AND EXISTS (
                    SELECT 1
                    FROM subscribers filter_sub
                    WHERE filter_sub.Subscriber_ID = :uid5
                      AND filter_sub.Session_ID = s.id
                      AND filter_sub.status <> 'rejected'
              )
            ORDER BY s.date DESC, s.time DESC";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uid1' => $currentUserIdValue,
                'uid2' => $currentUserIdValue,
                'uid3' => $currentUserIdValue,
                'uid4' => $currentUserIdValue,
                'uid5' => $currentUserIdValue,
                'viewer_university1' => $viewerUniversity,
                'viewer_university2' => $viewerUniversity
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch subscribed sessions: " . $e->getMessage());
        }
    }
}
