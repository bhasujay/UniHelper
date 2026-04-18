<?php

namespace app\models;

use app\core\Database;
use PDO;
use PDOException;
use Exception;

require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '\models\notify.php';

Class Connection extends BaseModel
{
    private $notify;
    public function __construct()
    {
        parent::__construct();
        $this->table = 'connections';
        $this->notify = new Notify();
    }

    // state: 'private'|'public'|'friend'
    // for server side rendering of profile pages, to determine how much info to return about the target user.
    // also can be used to reload the profile after a connection status change (e.g. from public to friend after accepting a request).
    public function getTargetUser($userID, $state){
        $state = strtolower($state ?? 'public');
        if (!in_array($state, ['private','public','friend'])) {
            $state = 'public';
        }

        if ($state === 'private') {
            $sql = "SELECT id, first_name, last_name, role, profile_picture FROM users WHERE id = :id";
        } elseif ($state === 'public') {
            $sql = "SELECT id, first_name, last_name, email, role, al_year, university, major, profile_role, profile_picture, created_at, public, moderator
                    FROM users WHERE id = :id";
        } else { // friend
            $sql = "SELECT id, first_name, last_name, email, phone, role, al_year, university, major, profile_role, profile_picture, created_at, public, moderator
                    FROM users WHERE id = :id";
        }

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $userID]);
            $user = $stmt->fetch(PDO::FETCH_OBJ);
            return $user ?: null;
        } catch (PDOException $e) {
            throw new Exception("Failed to fetch target user: " . $e->getMessage());
        }
    }

    /**
     * Returns the connection row between two users, or false if none exists.
     * Checks both directions (requester->receiver and receiver->requester).
     */
    public function checkStatus($userId, $friendId)
    {
        $sql = "SELECT * FROM connections
                WHERE ((requester_id = :uid AND receiver_id = :fid)
                   OR (requester_id = :fid2 AND receiver_id = :uid2))
                   AND status != 'rejected'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uid'  => $userId,
                ':fid'  => $friendId,
                ':fid2' => $friendId,
                ':uid2' => $userId,
            ]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to check connection status: " . $e->getMessage());
        }
    }

    /**
     * Inserts a new pending connection request from $userId to $friendId.
     */
    public function requestConnection($userId, $friendId)
    {
        // Delete any existing (e.g. rejected or stuck) row to overwrite safely
        $sqlDel = "DELETE FROM connections 
                   WHERE (requester_id = :uid AND receiver_id = :fid) 
                      OR (requester_id = :fid2 AND receiver_id = :uid2)";
        $sqlIns = "INSERT INTO connections (requester_id, receiver_id, status) VALUES (:uid, :fid, 'pending')";
        try {
            $stmtDel = $this->db->prepare($sqlDel);
            $stmtDel->execute([
                ':uid' => $userId, 
                ':fid' => $friendId,
                ':uid2' => $userId,
                ':fid2' => $friendId
            ]);

            $stmt = $this->db->prepare($sqlIns);
            $stmt->execute([':uid' => $userId, ':fid' => $friendId]);

            // send the notification to the receiver
            $this->notify->insertNotification(
                $friendId,
                "You have a new connection request from " . $this->getTargetUser($userId, 'public')->first_name,
                'connection',
                "/unihelper/view/profile/" . $userId
            );

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to create connection request: " . $e->getMessage());
        }
    }

    /**
     * Marks a pending request as accepted.
     * $userId is the receiver (the one accepting), $friendId is the requester.
     */
    public function acceptConnection($userId, $friendId)
    {
        $sql = "UPDATE connections SET status = 'accepted'
                WHERE requester_id = :fid AND receiver_id = :uid AND status = 'pending'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId, ':fid' => $friendId]);

            // send the notification to the requester
            $this->notify->insertNotification(
                $friendId,
                "Your connection request to " . $this->getTargetUser($userId, 'public')->first_name . " has been accepted",
                'connection',
                "/unihelper/view/profile/" . $userId
            );

            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to accept connection: " . $e->getMessage());
        }
    }

    /**
     * Marks a pending request as rejected.
     * $userId is the receiver (the one rejecting), $friendId is the requester.
     */
    public function rejectConnection($userId, $friendId)
    {
        $sql = "DELETE FROM connections
                WHERE requester_id = :fid AND receiver_id = :uid AND status = 'pending'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId, ':fid' => $friendId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to reject connection: " . $e->getMessage());
        }
    }

    /**
     * Cancels a pending request.
     * $userId is the requester (the one canceling), $friendId is the receiver.
     */
    public function cancelConnection($userId, $friendId)
    {
        $sql = "DELETE FROM connections
                WHERE requester_id = :uid AND receiver_id = :fid AND status = 'pending'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId, ':fid' => $friendId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to cancel connection: " . $e->getMessage());
        }
    }

    /**
     * Deletes an accepted connection between two users (either direction).
     */
    public function removeConnection($userId, $friendId)
    {
        $sql = "DELETE FROM connections
                WHERE (requester_id = :uid AND receiver_id = :fid)
                   OR (requester_id = :fid2 AND receiver_id = :uid2)";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uid'  => $userId,
                ':fid'  => $friendId,
                ':fid2' => $friendId,
                ':uid2' => $userId,
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to remove connection: " . $e->getMessage());
        }
    }

    /**
     * Returns all accepted connections for a user, joined with user details.
     */
    public function getConnections($userId)
    {
        $sql = "SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.profile_picture,
                    u.role,
                    u.university,
                    u.major,
                    c.created_at AS connected_at
                FROM connections c
                JOIN users u ON (
                    CASE
                        WHEN c.requester_id = :uid THEN c.receiver_id
                        ELSE c.requester_id
                    END = u.id
                )
                WHERE (c.requester_id = :uid2 OR c.receiver_id = :uid3)
                  AND c.status = 'accepted'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId, ':uid2' => $userId, ':uid3' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get connections: " . $e->getMessage());
        }
    }

    /**
     * Returns all pending requests received by $userId.
     */
    public function getPendingRequests($userId)
    {
        $sql = "SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.profile_picture,
                    u.role,
                    c.created_at AS requested_at
                FROM connections c
                JOIN users u ON c.requester_id = u.id
                WHERE c.receiver_id = :uid AND c.status = 'pending'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get pending requests: " . $e->getMessage());
        }
    }

    /**
     * Returns accepted friends for a user.
     * Contract: [{user_id, name, profile_picture, role}, ...]
     */
    public function getFriends($userId, $limit = 100)
    {
        $limit = max(1, min(100, (int) $limit));

        $sql = "SELECT
                    u.id AS user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.profile_picture,
                    u.role
                FROM connections c
                JOIN users u ON u.id = CASE
                    WHEN c.requester_id = :uid THEN c.receiver_id
                    ELSE c.requester_id
                END
                WHERE (c.requester_id = :uid2 OR c.receiver_id = :uid3)
                  AND c.status = 'accepted'
                ORDER BY c.created_at DESC
                LIMIT $limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uid' => $userId,
                ':uid2' => $userId,
                ':uid3' => $userId,
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get friends: " . $e->getMessage());
        }
    }

    /**
     * Returns pending requests sent by a user.
     * Contract: [{user_id, name, profile_picture, role}, ...]
     */
    public function getPendingConnections($userId, $limit = 100)
    {
        $limit = max(1, min(100, (int) $limit));

        $sql = "SELECT
                    u.id AS user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.profile_picture,
                    u.role
                FROM connections c
                JOIN users u ON u.id = c.receiver_id
                WHERE c.requester_id = :uid
                  AND c.status = 'pending'
                ORDER BY c.created_at DESC
                LIMIT $limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get pending connections: " . $e->getMessage());
        }
    }

    /**
     * Returns pending requests received by a user.
     * Contract: [{user_id, name, profile_picture, role}, ...]
     */
    public function getReceivedRequests($userId, $limit = 100)
    {
        $limit = max(1, min(100, (int) $limit));

        $sql = "SELECT
                    u.id AS user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.profile_picture,
                    u.role
                FROM connections c
                JOIN users u ON u.id = c.requester_id
                WHERE c.receiver_id = :uid
                  AND c.status = 'pending'
                ORDER BY c.created_at DESC
                LIMIT $limit";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':uid' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get received requests: " . $e->getMessage());
        }
    }

    /**
     * Returns mutual-friend based suggestions.
     * Process:
     * 1) Pick up to 10 random accepted friends of the user.
     * 2) Collect friends of those random friends (deduplicated).
     * 3) Exclude authenticated user and direct friends.
     * 4) Return up to 30 public accounts in random order.
     * Contract: [{user_id, name, profile_picture, role}, ...]
     */
    public function getMutualSuggestions($userId, $randomFriendSample = 10, $limit = 30)
    {
        $limit = max(1, min(30, (int) $limit));

        try {
            $sql = "SELECT
                        cand.id AS user_id,
                        CONCAT(cand.first_name, ' ', cand.last_name) AS name,
                        cand.profile_picture,
                        cand.role,
                        COUNT(DISTINCT f.friend_id) AS mutual_count
                    FROM (
                        SELECT DISTINCT
                            CASE
                                WHEN c1.requester_id = :uid_f1 THEN c1.receiver_id
                                ELSE c1.requester_id
                            END AS friend_id
                        FROM connections c1
                        WHERE c1.status = 'accepted'
                          AND (c1.requester_id = :uid_f2 OR c1.receiver_id = :uid_f3)
                    ) f
                    JOIN connections c2
                      ON c2.status = 'accepted'
                     AND (c2.requester_id = f.friend_id OR c2.receiver_id = f.friend_id)
                    JOIN users cand
                      ON cand.id = CASE
                            WHEN c2.requester_id = f.friend_id THEN c2.receiver_id
                            ELSE c2.requester_id
                         END
                                        LEFT JOIN connections direct
                                            ON ((direct.requester_id = :uid_d1 AND direct.receiver_id = cand.id)
                                             OR (direct.requester_id = cand.id AND direct.receiver_id = :uid_d2))
                                         AND direct.status IN ('pending', 'accepted')
                    WHERE cand.id <> :uid_self
                      AND cand.public = 1
                      AND direct.requester_id IS NULL
                    GROUP BY cand.id, cand.first_name, cand.last_name, cand.profile_picture, cand.role
                    ORDER BY mutual_count DESC, RAND()
                    LIMIT $limit";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':uid_f1' => $userId,
                ':uid_f2' => $userId,
                ':uid_f3' => $userId,
                ':uid_d1' => $userId,
                ':uid_d2' => $userId,
                ':uid_self' => $userId,
            ]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get mutual suggestions: " . $e->getMessage());
        }
    }
}