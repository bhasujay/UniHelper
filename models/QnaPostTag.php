<?php

namespace app\models;

use app\core\Database;

class QnaPostTag
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Associate a tag with a post
     * 
     * @param int $postId Post ID
     * @param int $tagId Tag ID
     * @return bool Success or failure
     */
    public function create($postId, $tagId)
    {
        try {
            $sql = "INSERT INTO qna_post_tags (post_id, tag_id) VALUES (?, ?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId, $tagId]);
            
            return true;
        } catch (\Exception $e) {
            error_log("QnaPostTag model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete all tag associations for a specific post
     * 
     * @param int $postId The post ID to remove associations for
     * @return bool True if successful, false otherwise
     */
    public function deleteByPostId($postId)
    {
        try {
            $sql = "DELETE FROM qna_post_tags WHERE post_id = ?";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([$postId]);
            return $result;
        } catch (\Exception $e) {
            error_log("QnaPostTag model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all tags for a post
     * 
     * @param int $postId Post ID
     * @return array Array of tags
     */
    public function getTagsByPost($postId)
    {
        try {
            $sql = "SELECT t.* FROM tags t 
                    JOIN qna_post_tags pt ON t.tag_id = pt.tag_id 
                    WHERE pt.post_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId]);
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("QnaPostTag model error: " . $e->getMessage());
            return [];
        }
    }
}