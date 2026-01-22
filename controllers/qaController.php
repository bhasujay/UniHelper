<?php

namespace app\controllers;

require_once dirname(__DIR__) . '\models\qa.php';
require_once dirname(__DIR__) . '\models\User.php';

use app\models\Qna;
use app\core\Request;
use app\models\User;


class QaController
{
    private $model;
    private $userModel;
    
    public function __construct()
    {
        $this->model = new Qna();
        $this->userModel = new User();
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
        
        if (strlen($question) < 10 || strlen($question) > 512) {
            echo json_encode([
                'success' => false,
                'message' => 'Question title must be between 10 and 512 characters'
            ]);
            return;
        }
        
        if (strlen($text) < 10) {
            echo json_encode([
                'success' => false,
                'message' => 'Description must be at least 10 characters'
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

    public function getQuestions(Request $request)
    {
        $offset = $request->get('offset');
        $limit = $request->get('limit');
        header('Content-Type: application/json');
        
        try {
            $questions = $this->model->getQuestionBatch($offset, $limit);

            // For each question, get the vote status for the current user
            foreach ($questions as &$question) {
                $question['user_vote'] = $this->model->checkUserVoteStatus($question['q_id'], $_SESSION['user_id']);
                $data = $this->userModel->getBasicInfo($question['user_id']);
                $question['username'] = $data['first_name'] . ' ' . $data['last_name'];
                $question['user_role'] = ucfirst(explode('-', $data['role'])[1]);
                $question['moderator_status'] = $data['moderator'];
                $question['user_avatar'] = $data['profile_picture'];
            }



            echo json_encode([
            'success' => true,
            'data' => empty($questions) ? null : $questions
            ]);
        } catch (\Exception $e) {
            echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
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

