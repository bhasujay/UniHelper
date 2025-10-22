<?php

namespace app\models;

use PDO;
use PDOException;
use Exception;

class WishlistModel extends BaseModel {
    protected $table = 'wishlist';
    protected $primaryKey = 'wishlist_id';
    
    /**
     * Add program to user's wishlist
     */
    public function addToWishlist($userId, $programId) {
        // Check if already in wishlist
        if ($this->isInWishlist($userId, $programId)) {
            throw new Exception('Program already in wishlist');
        }
        
        $data = [
            'user_id' => $userId,
            'program_id' => $programId
        ];
        
        return $this->create($data);
    }
    
    /**
     * Remove program from user's wishlist
     */
    public function removeFromWishlist($userId, $programId) {
        $sql = "DELETE FROM {$this->table} 
                WHERE user_id = :user_id AND program_id = :program_id";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'program_id' => $programId
            ]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to remove from wishlist: " . $e->getMessage());
        }
    }
    
    /**
     * Check if program is in user's wishlist
     */
    public function isInWishlist($userId, $programId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} 
                WHERE user_id = :user_id AND program_id = :program_id";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                'user_id' => $userId,
                'program_id' => $programId
            ]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to check wishlist: " . $e->getMessage());
        }
    }
    
    /**
     * Get user's complete wishlist with program details
     */
    public function getUserWishlist($userId, $limit = null, $offset = null) {
        $sql = "SELECT w.*, dp.name as program_name, dp.stream, dp.unicode, dp.descriptions, dp.duration,
                       u.name as university_name, m.name as major_name
                FROM {$this->table} w
                JOIN degree_program dp ON w.program_id = dp.program_id
                JOIN universities u ON dp.university_id = u.id
                JOIN majors m ON dp.major_id = m.id
                WHERE w.user_id = :user_id
                ORDER BY w.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get wishlist: " . $e->getMessage());
        }
    }
    
    /**
     * Get wishlist count for user
     */
    public function getWishlistCount($userId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception("Failed to get wishlist count: " . $e->getMessage());
        }
    }
}
