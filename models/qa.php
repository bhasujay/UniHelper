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
        // Set default values if not provided
        if (!isset($data['vote_count'])) {
            $data['vote_count'] = 0;
        }
        if (!isset($data['status'])) {
            $data['status'] = 'normal';
        }

        // Extract tags (if any) and remove them from $data so parent::create
        // does not try to insert a non-existent 'tags' column into questions
        $tags = [];
        if (isset($data['tags']) && is_array($data['tags'])) {
            $tags = $data['tags'];
        }
        if (isset($data['tags'])) {
            unset($data['tags']);
        }

        // Call parent create
        $questionId = parent::create($data);

        // Insert tags skipping duplicates (MySQL 8.0+ supports INSERT IGNORE or ON DUPLICATE KEY UPDATE)
        $tagStmt = $this->db->prepare("INSERT IGNORE INTO tags (tag_name) VALUES (:tag_name)");
        foreach ($tags as $tag) {
            $tagStmt->execute(['tag_name' => $tag]);
        }
        // Get tag IDs
        $tagIds = [];
        $stmt = $this->db->prepare("SELECT tag_id FROM tags WHERE tag_name = :tag_name");
        foreach ($tags as $tag) {
            $stmt->execute(['tag_name' => $tag]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($result) {
                $tagIds[] = $result['tag_id'];
            }
        }
        // update the post count for each tag
        $updateTagStmt = $this->db->prepare("UPDATE tags SET post_count = post_count + 1 WHERE tag_id = :tag_id");
        foreach ($tagIds as $tagId) {
            $updateTagStmt->execute(['tag_id' => $tagId]);
        }
        // Insert into question_tags
        $questionTagStmt = $this->db->prepare("INSERT INTO qa_tag (q_id, tag_id) VALUES (:q_id, :tag_id)");
        foreach ($tagIds as $tagId) {
            $questionTagStmt->execute(['q_id' => $questionId, 'tag_id' => $tagId]);
        }
        
        // Now update the timestamps using MySQL's NOW()
        $sql = "UPDATE questions SET added_time = NOW(), last_modified = NOW() WHERE q_id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $questionId]);
        
        return $questionId;
    }
    
    public function answerCreate($data)
    {
        // Set default values
        if (!isset($data['status'])) {
            $data['status'] = 'normal';
        }
        
        // Insert the answer
        $sql = "INSERT INTO answers (q_id, user_id, text, status, added_time) 
                VALUES (:q_id, :user_id, :text, :status, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'q_id' => $data['q_id'],
            'user_id' => $data['user_id'],
            'text' => $data['text'],
            'status' => $data['status']
        ]);
        
        $answerId = $this->db->lastInsertId();
        
        // Update the answer count in the questions table
        $updateSql = "UPDATE questions 
                      SET answer_count = answer_count + 1 
                      WHERE q_id = :q_id";
        $updateStmt = $this->db->prepare($updateSql);
        $updateStmt->execute(['q_id' => $data['q_id']]);
        
        return $answerId;
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

    public function getQuestionBatch(int $offset, int $limit, string $tag): array
    {
        // we only take normal questions
        // reported questions are only accessible to admins
        if ($tag === 'default') {
            $sql = "
                SELECT *
                FROM questions
                WHERE status = 'normal'
                ORDER BY vote_count DESC, answer_count DESC, added_time DESC, last_modified DESC
                LIMIT :offset, :limit
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        }

        // if filter is not default, we filter by tag
        $sql = "
            SELECT q.* 
            FROM questions q JOIN qa_tag qt ON q.q_id = qt.q_id JOIN tags t ON t.tag_id = qt.tag_id
            WHERE q.status = 'normal' AND t.tag_name = :tag
            ORDER BY q.vote_count DESC, q.answer_count DESC, q.added_time DESC, q.last_modified DESC
            LIMIT :offset, :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tag', $tag, \PDO::PARAM_STR);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getQuestionById($questionId)
    {
        $sql = "SELECT * FROM questions WHERE q_id = :questionId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':questionId', (int)$questionId, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getAnswersByQuestionId($questionID)
    {
        $sql = "SELECT * FROM answers WHERE q_id = :questionID";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':questionID', (int)$questionID, \PDO::PARAM_INT);
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

    public function vote($questionId, $userId, $voteValue)
    {
        $newVote = (int)$voteValue;
        $oldVote = $this->checkUserVoteStatus($questionId, $userId);

        $delta = $newVote - $oldVote;

        if ($oldVote == 0) {
            // add new vote
            $this->db->prepare(
                "INSERT INTO question_votes (q_id, user_id, vote)
                VALUES (:q_id, :user_id, :vote)"
            )->execute([
                'q_id' => $questionId,
                'user_id' => $userId,
                'vote' => $newVote
            ]);
        } 
        else {
            if ($newVote == 0) {
                // remove vote
                $this->db->prepare(
                    "DELETE FROM question_votes 
                    WHERE q_id = :q_id AND user_id = :user_id"
                )->execute([
                    'q_id' => $questionId,
                    'user_id' => $userId
                ]);
            } 
            else {
                // update vote
                $this->db->prepare(
                    "UPDATE question_votes 
                    SET vote = :vote 
                    WHERE q_id = :q_id AND user_id = :user_id"
                )->execute([
                    'vote' => $newVote,
                    'q_id' => $questionId,
                    'user_id' => $userId
                ]);
            }
        }
               
        // update question vote count
        $this->db->prepare(
            "UPDATE questions
            SET vote_count = vote_count + :delta
            WHERE q_id = :q_id"
        )->execute([
            'delta' => $delta,
            'q_id' => $questionId
        ]);
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

    public function deleteQuestion($questionId, $userId)
    {
        // Get the question first to verify it exists and check ownership
        $question = $this->getQuestionById($questionId);
        // send the question data to the logger for debugging
        if (!$question) {
            throw new \Exception('Question not found');
        }
        
        // Check if user has permission to delete (owner or admin)
        // For now, only the owner can delete their own question
        // You can add admin check here later
        if ($question['user_id'] != $userId) {
            throw new \Exception('You do not have permission to delete this question');
        }
        
        // Get associated tags to update their post counts
        $sql = "SELECT t.tag_id FROM tags t JOIN qa_tag qt ON t.tag_id = qt.tag_id WHERE qt.q_id = :questionId";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':questionId', (int)$questionId, \PDO::PARAM_INT);
        $stmt->execute();
        $tagIds = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Handle the image deletion
        if ($question['img_path']) {
            $imagePaths = explode(',', $question['img_path']);
            foreach ($imagePaths as $path) {
                $fullPath = dirname(__DIR__) . '/' . $path;
                if (file_exists($fullPath)) {
                    unlink($fullPath);
                }
            }
            
            // Try to remove the question directory if empty
            $questionDir = dirname(__DIR__) . '/public/uploads/qnaImages/' . $questionId;
            if (file_exists($questionDir) && is_dir($questionDir)) {
                @rmdir($questionDir);
            }
        }

        // Delete the question (this will also delete entries in qa_tag, answers, and votes due to foreign key constraints)
        $deleteSql = "DELETE FROM questions WHERE q_id = :questionId";
        $deleteStmt = $this->db->prepare($deleteSql);
        $deleteStmt->bindValue(':questionId', (int)$questionId, \PDO::PARAM_INT);
        $deleteStmt->execute();
        
        // Check if deletion was successful
        $rowsAffected = $deleteStmt->rowCount();
        if ($rowsAffected === 0) {
            throw new \Exception('Failed to delete question - no rows affected');
        }

        // Update post counts for associated tags
        if (!empty($tagIds)) {
            $updateTagSql = "UPDATE tags SET post_count = post_count - 1 WHERE tag_id IN (" . implode(',', array_map('intval', $tagIds)) . ")";
            $this->db->exec($updateTagSql);
        }

        // delete the tags that are no longer associated with any questions
        $cleanupTagSql = "DELETE FROM tags WHERE post_count <= 0";
        $this->db->exec($cleanupTagSql);

        return true;
    }

}
