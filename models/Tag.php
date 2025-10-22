<?php

namespace app\models;

use app\core\Database;

class Tag
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    /**
     * Get a tag by name, or create it if it doesn't exist
     * 
     * @param string $tagName Name of the tag
     * @return int|false Tag ID if successful, false otherwise
     */
    public function getOrCreate($tagName)
    {
        try {
            // Check if tag exists
            $sql = "SELECT tag_id FROM tags WHERE tag_name = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tagName]);
            $tag = $stmt->fetch();
            
            if ($tag) {
                return $tag['tag_id'];
            }
            
            // Create new tag
            $sql = "INSERT INTO tags (tag_name) VALUES (?)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tagName]);
            
            return $this->db->lastInsertId();
        } catch (\Exception $e) {
            error_log("Tag model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all tags
     * 
     * @return array Array of tags
     */
    public function getAll()
    {
        try {
            $sql = "SELECT * FROM tags ORDER BY tag_name";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Tag model error: " . $e->getMessage());
            return [];
        }
    }
}