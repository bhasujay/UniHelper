<?php

namespace app\models;

use app\core\Database;

class QnaPost
{
    private $db;
    private $tagModel;
    private $postTagModel;
    private $hierarchyModel;
    
    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tagModel = new Tag();
        $this->postTagModel = new QnaPostTag();
        $this->hierarchyModel = new QnaHierarchy();
    }
    
    /**
     * Create a new question post
     * 
     * @param int $userId User ID of the author
     * @param string $title Question title
     * @param string $body Question content
     * @param array $tagNames Array of tag names
     * @return int|false Post ID if successful, false otherwise
     */
    public function createQuestion($userId, $title, $body, $tagNames = [])
    {
        try {
            $this->db->getConnection()->beginTransaction();
            
            // Create post
            $sql = "INSERT INTO qna_posts (user_id, title, body, created_at) VALUES (?, ?, ?, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $title, $body]);
            
            $postId = $this->db->lastInsertId();
            
            // Process tags
            foreach ($tagNames as $tagName) {
                if (empty($tagName)) continue;
                
                // Get or create tag
                $tagId = $this->tagModel->getOrCreate($tagName);
                
                if ($tagId) {
                    // Associate tag with post
                    $this->postTagModel->create($postId, $tagId);
                }
            }
            
            $this->db->getConnection()->commit();
            return $postId;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("QnaPost model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get recent questions
     * 
     * @param int $limit Number of questions to retrieve
     * @param int $offset Starting offset
     * @return array Array of questions
     */
    public function getRecentQuestions($limit = 10, $offset = 0)
    {
        try {
            // Get questions with basic user info
            $sql = "SELECT 
                      p.*, 
                      u.first_name,
                      u.last_name,
                      u.profile_picture
                    FROM 
                      qna_posts p
                      LEFT JOIN users u ON p.user_id = u.id
                    WHERE
                      p.title IS NOT NULL
                    ORDER BY 
                      p.created_at DESC
                    LIMIT ? OFFSET ?";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit, $offset]);
            
            $questions = $stmt->fetchAll();
            
            // For each question, get the tags and answer count
            foreach ($questions as &$question) {
                $question['tags'] = $this->postTagModel->getTagsByPost($question['post_id']);
                $question['answer_count'] = $this->hierarchyModel->countAnswers($question['post_id']);
            }
            
            return $questions;
        } catch (\Exception $e) {
            error_log("QnaPost model error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all tags for the Q&A system
     * 
     * @return array Array of all tags
     */
    public function getAllTags()
    {
        return $this->tagModel->getAll();
    }
}