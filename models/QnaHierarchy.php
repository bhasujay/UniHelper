<?php

namespace app\models;

use app\core\Database;

class QnaHierarchy
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Create a hierarchy relationship between posts
     * 
     * @param int $parentId Parent post ID
     * @param int $childId Child post ID (reply)
     * @return bool Success or failure
     */
    public function create($parentId, $childId)
    {
        try {
            $sql = "INSERT INTO qna_hierarchy (parent_id, child_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$parentId, $childId]);
            
            return true;
        } catch (\Exception $e) {
            error_log("QnaHierarchy model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all answers (children) for a question
     * 
     * @param int $parentId Parent post ID
     * @return array Array of child post IDs
     */
    public function getAnswers($parentId)
    {
        try {
            $sql = "SELECT child_id FROM qna_hierarchy WHERE parent_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$parentId]);
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("QnaHierarchy model error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Count answers for a post
     * 
     * @param int $parentId Parent post ID
     * @return int Number of answers
     */
    public function countAnswers($parentId)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM qna_hierarchy WHERE parent_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$parentId]);
            $result = $stmt->fetch();
            
            return $result['count'] ?? 0;
        } catch (\Exception $e) {
            error_log("QnaHierarchy model error: " . $e->getMessage());
            return 0;
        }
    }
}