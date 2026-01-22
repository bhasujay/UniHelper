<?php

namespace app\models;

use app\core\Database;

require_once dirname(__DIR__) . '\models\base-model.php';

class Qna extends BaseModel
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'questions';
        $this->primaryKey = 'q_id';
    }

    public function create($data)
    {
        // Set added_time and last_modified to the same value
        $currentTime = date('Y-m-d H:i:s');
        $data['added_time'] = $currentTime;
        $data['last_modified'] = $currentTime;
        
        // Set default values if not provided
        if (!isset($data['vote_count'])) {
            $data['vote_count'] = 0;
        }
        if (!isset($data['status'])) {
            $data['status'] = 'normal';
        }
        
        return parent::create($data);
    }
    
    // Handle multiple image uploads
    public function handleImageUploads($files, $questionId)
    {
        if (empty($files) || !isset($files['name']) || empty($files['name'][0])) {
            return null;
        }
        
        // Create directory for this question
        $uploadDir = dirname(__DIR__) . '/public/uploads/qnaImages/' . $questionId;
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $imagePaths = [];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        foreach ($files['name'] as $index => $fileName) {
            $tmpName = $files['tmp_name'][$index];
            $fileError = $files['error'][$index];
            
            if ($fileError !== UPLOAD_ERR_OK) {
                continue;
            }
            
            // Get file extension
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            
            // Validate extension
            if (!in_array($extension, $allowedExtensions)) {
                continue;
            }
            
            // New filename: 0.jpg, 1.jpg, 2.jpg, etc.
            $newFileName = $index . '.' . $extension;
            $destination = $uploadDir . '/' . $newFileName;
            
            if (move_uploaded_file($tmpName, $destination)) {
                $imagePaths[] = 'public/uploads/qnaImages/' . $questionId . '/' . $newFileName;
            }
        }
        
        // Return comma-separated paths or null if no images uploaded
        return !empty($imagePaths) ? implode(',', $imagePaths) : null;
    }

    public function getAllQuestions()
    {
        $sql = "SELECT * FROM questions ORDER BY added_time DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQuestionBatch(int $offset, int $limit = 10): array
    {
        $sql = "
            SELECT *
            FROM questions
            WHERE status = 'normal'
            ORDER BY vote_count ASC, q_id ASC
            LIMIT :offset, :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // tag related functions
    public function getTopTags()
    {
        $sql = "SELECT tag_id, tag_name, post_count FROM tags ORDER BY post_count DESC LIMIT 10";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // vote related functions
    public function checkUserVoteStatus($questionId, $userId)
    {
        $sql = "SELECT vote FROM question_votes WHERE q_id = :questionId AND user_id = :userId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':questionId', (int)$questionId, \PDO::PARAM_INT);
        $stmt->bindValue(':userId', (int)$userId, \PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        // Return the vote value if found, otherwise 0 (no vote)
        return $result ? (int)$result['vote'] : 0;
    }

}
