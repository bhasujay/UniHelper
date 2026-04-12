<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/models/base-model.php';

use PDO;
use PDOException;
use Exception;

class Session_model extends BaseModel {
    protected $table = 'sessions';
    
    /**
     * Get all sessions excluding soft-deleted and manually deleted ones
     */
    public function findAll($conditions = [], $limit = null, $offset = null, $currentUserId = null) {
        $currentUserIdValue = (int)$currentUserId;
        $sql = "SELECT s.*, u.first_name as creator_name, uni.name as creator_university, 
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :current_user_id AND sub.Session_ID = s.id
                ) THEN 1 ELSE 0 END AS is_subscribed
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.deleted_at IS NULL AND s.is_deleted = 0";
        $params = ['current_user_id' => $currentUserIdValue];
        
        if (!empty($conditions)) {
            foreach ($conditions as $column => $value) {
                $sql .= " AND s.{$column} = :{$column}";
                $params[$column] = $value;
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
     * Get sessions created by a specific user (includes expired, excludes manually deleted)
     */
    public function findByUserId($userId, $limit = null, $offset = null, $currentUserId = null) {
        $currentUserIdValue = (int)$currentUserId;
        $sql = "SELECT s.*, u.first_name as creator_name, uni.name as creator_university, 
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :current_user_id AND sub.Session_ID = s.id
                ) THEN 1 ELSE 0 END AS is_subscribed
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
                'current_user_id' => $currentUserIdValue
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
        $sql = "SELECT s.*, u.first_name as creator_name, uni.name as creator_university, 
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :current_user_id AND sub.Session_ID = s.id
                ) THEN 1 ELSE 0 END AS is_subscribed
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.university = :university AND s.deleted_at IS NULL AND s.is_deleted = 0 
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
                'current_user_id' => $currentUserIdValue
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find sessions by university: " . $e->getMessage());
        }
    }
    
    /**
     * Get sessions by audience type (excludes expired and manually deleted)
     * $audience can be 'my_university' or 'all_universities'
     */
    public function findByAudience($audience, $userUniversity = null, $limit = null, $offset = null, $currentUserId = null) {
        if ($audience === 'my_university' && $userUniversity) {
            return $this->findByUniversity($userUniversity, $limit, $offset, $currentUserId);
        } elseif ($audience === 'all_universities') {
            return $this->findAll([], $limit, $offset, $currentUserId);
        }
        return [];
    }
    
    /**
     * Get sessions by subject (excludes expired and manually deleted)
     */
    public function findBySubject($subject, $limit = null, $offset = null, $currentUserId = null) {
        $currentUserIdValue = (int)$currentUserId;
        $sql = "SELECT s.*, u.first_name as creator_name, uni.name as creator_university, 
                CASE WHEN EXISTS (
                    SELECT 1 FROM subscribers sub
                    WHERE sub.Subscriber_ID = :current_user_id AND sub.Session_ID = s.id
                ) THEN 1 ELSE 0 END AS is_subscribed
            FROM {$this->table} s 
            LEFT JOIN users u ON s.user_id = u.id 
            LEFT JOIN universities uni ON u.university = uni.id 
                WHERE s.subject = :subject AND s.deleted_at IS NULL AND s.is_deleted = 0 
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
                'current_user_id' => $currentUserIdValue
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find sessions by subject: " . $e->getMessage());
        }
    }

    /**
     * Subscribe a user to a session
     */
    public function subscribe($userId, $sessionId) {
        $sql = "INSERT IGNORE INTO subscribers (Subscriber_ID, Session_ID)
                VALUES (:subscriber_id, :session_id)";

        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'subscriber_id' => $userId,
                'session_id' => $sessionId
            ]);

            // Increment count only when a new subscription row is inserted.
            if ($stmt->rowCount() > 0) {
                $updateSql = "UPDATE {$this->table}
                              SET sub_count = COALESCE(sub_count, 0) + 1
                              WHERE id = :session_id AND is_deleted = 0";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute(['session_id' => $sessionId]);
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Failed to subscribe to session: " . $e->getMessage());
        }
    }

    /**
     * Unsubscribe a user from a session
     */
    public function unsubscribe($userId, $sessionId) {
        $sql = "DELETE FROM subscribers
                WHERE Subscriber_ID = :subscriber_id AND Session_ID = :session_id";

        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'subscriber_id' => $userId,
                'session_id' => $sessionId
            ]);

            $deletedRows = $stmt->rowCount();

            // Decrement count only when an existing subscription row is deleted.
            if ($deletedRows > 0) {
                $updateSql = "UPDATE {$this->table}
                              SET sub_count = GREATEST(COALESCE(sub_count, 0) - 1, 0)
                              WHERE id = :session_id AND is_deleted = 0";
                $updateStmt = $this->db->prepare($updateSql);
                $updateStmt->execute(['session_id' => $sessionId]);
            }

            $pdo->commit();
            return $deletedRows > 0;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw new Exception("Failed to unsubscribe from session: " . $e->getMessage());
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
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Failed to update session: " . $e->getMessage());
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
}
