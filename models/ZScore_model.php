<?php

namespace app\models;

use PDO;
use PDOException;
use Exception;

class ZScoreModel extends BaseModel {
    protected $table = 'applications_z_scores';
    
    /**
     * Get Z-Score by user ID
     * Uses BaseModel::findAll() method
     */
    public function findByUserId($userId) {
        $results = $this->findAll(['user_id' => $userId], 1);
        return !empty($results) ? $results[0] : null;
    }
    
    /**
     * Check if user has existing Z-Score
     * Uses BaseModel::count() method
     */
    public function userHasZScore($userId) {
        return $this->count(['user_id' => $userId]) > 0;
    }
    
    /**
     * Update Z-Score by user ID
     * Custom implementation needed because BaseModel::update() uses id, not user_id
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
     * Custom implementation needed because BaseModel::delete() uses id, not user_id
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
     * Get user's interest profile for ranking boosts.
     * Returns null-major values when no preference is set.
     */
    public function getUserInterestProfile($userId) {
        $sql = "SELECT u.major AS major_id, m.name AS major_name
                FROM users u
                LEFT JOIN majors m ON u.major = m.id
                WHERE u.id = :user_id
                LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result || $result['major_id'] === null || $result['major_id'] === '') {
                return [
                    'major_id' => null,
                    'major_name' => null
                ];
            }

            return [
                'major_id' => (int) $result['major_id'],
                'major_name' => $result['major_name'] ?? null
            ];
        } catch (PDOException $e) {
            error_log('Failed to fetch user interest profile: ' . $e->getMessage());
            return [
                'major_id' => null,
                'major_name' => null
            ];
        }
    }
    
    /**
     * Create or update Z-Score (upsert functionality)
     * Uses BaseModel::create() method
     */
    public function saveZScore($userId, $data) {
        // Check if user already has Z-Score
        if ($this->userHasZScore($userId)) {
            // Update existing
            return $this->updateByUserId($userId, $data);
        } else {
            // Create new - uses inherited create() method
            $data['user_id'] = $userId;
            return $this->create($data);
        }
    }
}
