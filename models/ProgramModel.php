<?php

namespace app\models;

use PDO;
use PDOException;
use Exception;

class ProgramModel extends BaseModel {
    protected $table = 'degree_program';
    protected $primaryKey = 'program_id';
    
    /**
     * Search programs with filters
     */
    public function searchPrograms($searchTerm = '', $filters = []) {
        $sql = "SELECT dp.*, u.name as university_name, m.name as major_name 
                FROM {$this->table} dp 
                JOIN universities u ON dp.university_id = u.id 
                JOIN majors m ON dp.major_id = m.id
                WHERE 1=1";
        
        $params = [];
        
        // Text search
        if (!empty($searchTerm)) {
            $sql .= " AND (dp.name LIKE :search_term1 
                     OR u.name LIKE :search_term2 
                     OR m.name LIKE :search_term3
                     OR dp.unicode LIKE :search_term4)";
            $params['search_term1'] = "%{$searchTerm}%";
            $params['search_term2'] = "%{$searchTerm}%";
            $params['search_term3'] = "%{$searchTerm}%";
            $params['search_term4'] = "%{$searchTerm}%";
        }
        
        // University filter
        if (isset($filters['university_id']) && !empty($filters['university_id'])) {
            $sql .= " AND dp.university_id = :university_id";
            $params['university_id'] = $filters['university_id'];
        }
        
        // Stream filter
        if (isset($filters['stream']) && !empty($filters['stream'])) {
            $sql .= " AND LOWER(REPLACE(dp.stream, '-', ' ')) = LOWER(REPLACE(:stream, '-', ' '))";
            $params['stream'] = trim((string)$filters['stream']);
        }
        
        // Major filter
        if (isset($filters['major_id']) && !empty($filters['major_id'])) {
            $sql .= " AND dp.major_id = :major_id";
            $params['major_id'] = $filters['major_id'];
        }
        
        // Unicode filter
        if (isset($filters['unicode']) && !empty($filters['unicode'])) {
            $sql .= " AND dp.unicode LIKE :unicode";
            $params['unicode'] = "%{$filters['unicode']}%";
        }
        
        $sql .= " ORDER BY dp.name ASC";
        
        // Pagination
        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
            
            if (isset($filters['offset'])) {
                $sql .= " OFFSET " . (int)$filters['offset'];
            }
        }
        
        try {
            // Debug: Log the SQL and parameters
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Search failed: " . $e->getMessage() . " SQL: " . $sql . " Params: " . print_r($params, true));
        }
    }
    
    /**
     * Get all universities for filter dropdown
     */
    public function getAllUniversities() {
        $sql = "SELECT id as university_id, name FROM universities ORDER BY name ASC";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get universities: " . $e->getMessage());
        }
    }
    
    /**
     * Get all majors for filter dropdown
     */
    public function getAllMajors() {
        $sql = "SELECT id as major_id, name FROM majors ORDER BY name ASC";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get majors: " . $e->getMessage());
        }
    }
    
    /**
     * Get autocomplete suggestions
     */
    public function getAutocompleteSuggestions($term, $limit = 10) {
        $sql = "SELECT DISTINCT dp.name as suggestion 
                FROM {$this->table} dp 
                WHERE dp.name LIKE :term 
                ORDER BY dp.name ASC 
                LIMIT :limit";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute([
                'term' => "%{$term}%",
                'limit' => $limit
            ]);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            throw new Exception("Autocomplete failed: " . $e->getMessage());
        }
    }
    
    /**
     * Get programs by user's Z-score (for eligibility)
     */
    public function getEligiblePrograms($userZScore) {
        $sql = "SELECT dp.*, u.name as university_name, m.name as major_name 
                FROM {$this->table} dp 
                JOIN universities u ON dp.university_id = u.id 
                JOIN majors m ON dp.major_id = m.id 
                WHERE dp.cutoff_zscore <= :user_zscore 
                ORDER BY dp.cutoff_zscore ASC";
        
        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute(['user_zscore' => $userZScore]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Failed to get eligible programs: " . $e->getMessage());
        }
    }
}
