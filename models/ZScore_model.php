<?php

namespace app\models;

use PDO;
use PDOException;
use Exception;

class ZScoreModel extends BaseModel {
    protected $table = 'applications_z_scores';
    
    /**
     * Get Z-Score by user ID
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to find Z-Score by user ID: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user has existing Z-Score
     */
    public function userHasZScore($userId) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            throw new Exception("Failed to check if user has Z-Score: " . $e->getMessage());
        }
    }
    
    /**
     * Update Z-Score by user ID (instead of by record ID)
     */
    public function updateByUserId($userId, $data) {
        $setClause = [];
        foreach (array_keys($data) as $column) {
            $setClause[] = "{$column} = :{$column}";
        }
        $setClause = implode(', ', $setClause);
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE user_id = :user_id";
        
        $data['user_id'] = $userId;
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($data);
        } catch (PDOException $e) {
            throw new Exception("Failed to update Z-Score by user ID: " . $e->getMessage());
        }
    }
    
    /**
     * Delete Z-Score by user ID
     */
    public function deleteByUserId($userId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['user_id' => $userId]);
        } catch (PDOException $e) {
            throw new Exception("Failed to delete Z-Score by user ID: " . $e->getMessage());
        }
    }
    
    /**
     * Create or update Z-Score (upsert functionality)
     */
    public function saveZScore($userId, $data) {
        // Check if user already has Z-Score
        if ($this->userHasZScore($userId)) {
            // Update existing
            return $this->updateByUserId($userId, $data);
        } else {
            // Create new
            $data['user_id'] = $userId;
            return $this->create($data);
        }
    }
}
