<?php

namespace app\controllers;

use app\models\Qna;

require_once dirname(__DIR__) . '\models\qa.php';

class QaController
{
    private $model;
    
    public function __construct()
    {
        $this->model = new Qna();
    }
    
    public function create()
    {
        header('Content-Type: application/json');
        
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            echo json_encode([
                'success' => false,
                'message' => 'You must be logged in to post a question'
            ]);
            return;
        }
        
        // Validate input
        if (!isset($_POST['question']) || !isset($_POST['text'])) {
            echo json_encode([
                'success' => false,
                'message' => 'Question title and description are required'
            ]);
            return;
        }
        
        $question = trim($_POST['question']);
        $text = trim($_POST['text']);
        
        if (strlen($question) < 10 || strlen($question) > 255) {
            echo json_encode([
                'success' => false,
                'message' => 'Question title must be between 10 and 255 characters'
            ]);
            return;
        }
        
        if (strlen($text) < 20) {
            echo json_encode([
                'success' => false,
                'message' => 'Description must be at least 20 characters'
            ]);
            return;
        }
        
        try {
            // Prepare data for insertion
            $data = [
                'user_id' => $_SESSION['user_id'],
                'question' => $question,
                'text' => $text,
                'img_path' => null // Will be updated after upload
            ];
            
            // Create question
            $questionId = $this->model->create($data);
            
            // Handle image uploads if present
            if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
                $imagePaths = $this->model->handleImageUploads($_FILES['images'], $questionId);
                
                // Update question with image paths
                if ($imagePaths) {
                    $this->model->update($questionId, ['img_path' => $imagePaths]);
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Question posted successfully',
                'data' => ['question_id' => $questionId]
            ]);
            
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to post question: ' . $e->getMessage()
            ]);
        }
    }
    
    public function getTopTags()
    {
        header('Content-Type: application/json');
        
        try {
            $tags = $this->model->getTopTags();
            echo json_encode([
                'success' => true,
                'data' => $tags
            ]);
        } catch (\Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}