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
    
    /**
     * Edit an existing question post
     * 
     * @param int $postId ID of the post to edit
     * @param string $title Updated question title
     * @param string $body Updated question content
     * @param array $tagNames Array of tag names
     * @return bool True if successful, false otherwise
     */
    public function editQuestion($postId, $title, $body, $tagNames = [])
    {
        try {
            // First verify the post exists
            $checkSql = "SELECT post_id FROM qna_posts WHERE post_id = ? AND title IS NOT NULL";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$postId]);
            
            if ($checkStmt->rowCount() === 0) {
                return false; // Post doesn't exist or isn't a question
            }
            
            $this->db->getConnection()->beginTransaction();
            
            // Update post
            $sql = "UPDATE qna_posts SET title = ?, body = ?, updated_at = NOW() WHERE post_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$title, $body, $postId]);
            
            // Remove existing tag associations
            $this->postTagModel->deleteByPostId($postId);
            
            // Process new tags
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
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("QnaPost model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get a specific question by ID
     * 
     * @param int $postId The post ID to fetch
     * @return array|false Question data or false if not found
     */
    public function getQuestionById($postId)
    {
        try {
            // Get the question with basic user info
            $sql = "SELECT 
                      p.*, 
                      u.first_name,
                      u.last_name,
                      u.profile_picture
                    FROM 
                      qna_posts p
                      LEFT JOIN users u ON p.user_id = u.id
                    WHERE
                      p.post_id = ? AND p.title IS NOT NULL";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId]);
            
            $question = $stmt->fetch();
            
            if (!$question) {
                return false;
            }
            
            // Get the tags for this question
            $question['tags'] = $this->postTagModel->getTagsByPost($question['post_id']);
            $question['answer_count'] = $this->hierarchyModel->countAnswers($question['post_id']);
            
            return $question;
        } catch (\Exception $e) {
            error_log("QnaPost model error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update an existing question
     * 
     * @param int $postId ID of the post to update
     * @param string $title Updated question title
     * @param string $body Updated question content
     * @param array $tagNames Array of tag names
     * @return bool True if successful, false otherwise
     */
    public function updateQuestion($postId, $title, $body, $tagNames = [])
    {
        // This function already exists in your model as editQuestion
        return $this->editQuestion($postId, $title, $body, $tagNames);
    }
    
    /**
     * Delete a question
     * 
     * @param int $postId ID of the post to delete
     * @return bool True if successful, false otherwise
     */
    public function deleteQuestion($postId)
    {
        try {
            // First verify the post exists
            $checkSql = "SELECT post_id FROM qna_posts WHERE post_id = ? AND title IS NOT NULL";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->execute([$postId]);
            
            if ($checkStmt->rowCount() === 0) {
                return false; // Post doesn't exist or isn't a question
            }
            
            $this->db->getConnection()->beginTransaction();
            
            // Remove tag associations
            $this->postTagModel->deleteByPostId($postId);
            
            // Delete the post
            $sql = "DELETE FROM qna_posts WHERE post_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$postId]);
            
            $this->db->getConnection()->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->getConnection()->rollBack();
            error_log("QnaPost model error: " . $e->getMessage());
            return false;
        }
    }
}