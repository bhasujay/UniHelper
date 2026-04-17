<?php

namespace app\models;

require_once dirname(__DIR__) . '/core/Database.php';

use app\core\Database;
use \PDO;

/**
 * Feedback Model
 * 
 * Handles all database operations related to feedback.
 * Uses prepared statements with PDO to prevent SQL injection attacks.
 * 
 * Table structure (assumed):
 * - id (INT, PRIMARY KEY, AUTO INCREMENT)
 * - name (VARCHAR(100))
 * - title (VARCHAR(255))
 * - message (TEXT)
 * - created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
 * - updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP, ON UPDATE CURRENT_TIMESTAMP)
 */
class Feedback
{
    /**
     * Database connection instance (singleton pattern)
     * @var object
     */
    private $db;
    
    /**
     * Table name for feedback posts
     * @var string
     */
    private $tableName = 'feedback';
    
    /**
     * Constructor
     * Initializes database connection using the singleton Database class
     */
    public function __construct()
    {
        // Get database connection from singleton instance
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Insert a new feedback post into the database
     * 
     * This method:
     * 1. Uses a prepared statement to prevent SQL injection
     * 2. Binds parameters securely (preventing injection attacks)
     * 3. Executes the insert operation
     * 4. Returns success/error status with the inserted ID
     * 
     * @param string $name User's name
     * @param string $title Feedback title
     * @param string $message Feedback message content
     * 
     * @return array {
     *     'success' => bool,
     *     'id' => int (only if successful),
     *     'created_at' => string (only if successful),
     *     'error' => string (only if failed)
     * }
     */
    public function insertFeedback($name, $title, $message)
    {
        try {
            // STEP 1: Prepare SQL statement with placeholders
            // Placeholders (?) prevent SQL injection by separating data from SQL code
            $sql = "INSERT INTO {$this->tableName} (name, title, message) VALUES (?, ?, ?)";
            
            // STEP 2: Prepare the statement (compile and validate SQL)
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'error' => 'Failed to prepare statement'
                ];
            }
            
            // STEP 3: Bind parameters to the prepared statement
            // This ensures data is properly escaped and cannot interfere with SQL logic
            // PDO::PARAM_STR: treats parameters as strings
            $bindResult = $stmt->bindParam(1, $name, PDO::PARAM_STR);
            $bindResult = $stmt->bindParam(2, $title, PDO::PARAM_STR);
            $bindResult = $stmt->bindParam(3, $message, PDO::PARAM_STR);
            
            if (!$bindResult) {
                return [
                    'success' => false,
                    'error' => 'Failed to bind parameters'
                ];
            }
            
            // STEP 4: Execute the prepared statement
            // At this point, the SQL and data are completely separate
            $execResult = $stmt->execute();
            
            if (!$execResult) {
                $errorInfo = $stmt->errorInfo();
                return [
                    'success' => false,
                    'error' => 'Database error: ' . $errorInfo[2]
                ];
            }
            
            // STEP 5: Get the ID of the newly inserted row
            $newId = $this->db->lastInsertId();
            
            // STEP 6: Return success response with inserted data
            return [
                'success' => true,
                'id' => $newId,
                'created_at' => date('Y-m-d H:i:s') // Current timestamp
            ];
            
        } catch (\PDOException $e) {
            // Handle database exceptions (connection errors, constraint violations, etc.)
            return [
                'success' => false,
                'error' => 'Database exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all feedback posts (with optional pagination)
     * 
     * Retrieves feedback from the database with proper SQL injection prevention
     * through prepared statements.
     * 
     * @param int $limit Number of records to retrieve (default: 10)
     * @param int $offset Starting offset for pagination (default: 0)
     * 
     * @return array Array of feedback posts or empty array if none found
     */
    public function getAllFeedback($limit = 10, $offset = 0)
    {
        try {
            // Prepare statement with LIMIT and OFFSET for pagination
            $sql = "SELECT * FROM {$this->tableName} ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            
            // Bind limit and offset as integers to prevent injection
            $stmt->bindParam(1, $limit, PDO::PARAM_INT);
            $stmt->bindParam(2, $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            
            // Fetch all results as associative arrays
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get a single feedback post by ID
     * 
     * Uses prepared statement to safely retrieve feedback by ID
     * 
     * @param int $id Feedback post ID
     * 
     * @return array Single feedback post array or null if not found
     */
    public function getFeedbackById($id)
    {
        try {
            $sql = "SELECT * FROM {$this->tableName} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            $stmt->execute();
            
            // Fetch a single result
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\PDOException $e) {
            return null;
        }
    }
    
    /**
     * Get feedback count (for pagination statistics)
     * 
     * Returns the total number of feedback posts in the database
     * 
     * @return int Total feedback count
     */
    public function getFeedbackCount()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->tableName}";
            $stmt = $this->db->query($sql);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?? 0;
            
        } catch (\PDOException $e) {
            return 0;
        }
    }
    
    /**
     * Delete feedback post by ID
     * 
     * Safely deletes a feedback post using prepared statement
     * 
     * @param int $id Feedback post ID to delete
     * 
     * @return array {
     *     'success' => bool,
     *     'message' => string (optional)
     * }
     */
    public function deleteFeedback($id)
    {
        try {
            $sql = "DELETE FROM {$this->tableName} WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(1, $id, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                return ['success' => true];
            } else {
                return [
                    'success' => false,
                    'message' => 'Failed to delete feedback'
                ];
            }
            
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing feedback post
     * 
     * Updates the name, title, and message of a feedback post
     * 
     * @param int $id Feedback post ID
     * @param string $name User's name
     * @param string $title Feedback title
     * @param string $message Feedback message
     * 
     * @return array {
     *     'success' => bool,
     *     'error' => string (only if failed)
     * }
     */
    public function updateFeedback($id, $name, $title, $message)
    {
        try {
            // Prepare UPDATE statement with placeholders
            $sql = "UPDATE {$this->tableName} SET name = ?, title = ?, message = ? WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            
            if (!$stmt) {
                return [
                    'success' => false,
                    'error' => 'Failed to prepare statement'
                ];
            }
            
            // Bind parameters to the prepared statement
            $stmt->bindParam(1, $name, PDO::PARAM_STR);
            $stmt->bindParam(2, $title, PDO::PARAM_STR);
            $stmt->bindParam(3, $message, PDO::PARAM_STR);
            $stmt->bindParam(4, $id, PDO::PARAM_INT);
            
            // Execute the update
            $execResult = $stmt->execute();
            
            if (!$execResult) {
                $errorInfo = $stmt->errorInfo();
                return [
                    'success' => false,
                    'error' => 'Database error: ' . $errorInfo[2]
                ];
            }
            
            // Return success response
            return [
                'success' => true
            ];
            
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'error' => 'Database exception: ' . $e->getMessage()
            ];
        }
    }
}

