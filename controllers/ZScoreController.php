<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/ZScore_model.php';

use app\models\ZScoreModel;
use app\core\Request;

/**
 * Controller for Z-Score operations
 * Handles saving, retrieving, and deleting user Z-Scores
 */
class ZScoreController
{
    private $zScoreModel;
    
    public function __construct() {
        $this->zScoreModel = new ZScoreModel();
    }
    
    /**
     * Save or update user's Z-Score
     * POST /api?controller=ZScoreController&action=saveZScore
     */
    public function saveZScore(Request $request) {
        try {
            // Check if user is authenticated
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            // Get and validate input data
            $data = $this->validateZScoreData($_POST);
            if (!$data) {
                return; // Error already sent in validation
            }
            
            $userId = $_SESSION['user_id'];
            
            // Check if user already has Z-Score to determine action
            $isUpdate = $this->zScoreModel->userHasZScore($userId);
            
            // Save Z-Score using model
            $result = $this->zScoreModel->saveZScore($userId, $data);
            
            if ($result) {
                // Get the saved data to return
                $savedZScore = $this->zScoreModel->findByUserId($userId);
                $action = $isUpdate ? 'updated' : 'saved';
                $this->sendJsonResponse(true, "Z-Score {$action} successfully", $savedZScore);
            } else {
                $action = $isUpdate ? 'update' : 'save';
                $this->sendJsonResponse(false, "Failed to {$action} Z-Score", null, 500);
            }
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get user's Z-Score
     * GET /api?controller=ZScoreController&action=getZScore
     */
    public function getZScore(Request $request) {
        try {
            // Check if user is authenticated
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            
            // Get Z-Score from database
            $zScore = $this->zScoreModel->findByUserId($userId);
            
            if ($zScore) {
                $this->sendJsonResponse(true, 'Z-Score retrieved successfully', $zScore);
            } else {
                $this->sendJsonResponse(true, 'No Z-Score found', null);
            }
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Delete user's Z-Score
     * DELETE /api?controller=ZScoreController&action=deleteZScore
     */
    public function deleteZScore(Request $request) {
        try {
            // Check if user is authenticated
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            
            // Check if user has existing Z-Score
            if (!$this->zScoreModel->userHasZScore($userId)) {
                $this->sendJsonResponse(false, 'No Z-Score found to delete', null, 404);
                return;
            }
            
            // Delete Z-Score
            $result = $this->zScoreModel->deleteByUserId($userId);
            
            if ($result) {
                $this->sendJsonResponse(true, 'Z-Score deleted successfully', null);
            } else {
                $this->sendJsonResponse(false, 'Failed to delete Z-Score', null, 500);
            }
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Find eligible degree programs based on user's Z-Score
     * GET /api?controller=ZScoreController&action=findEligibleDegrees
     */
    public function findEligibleDegrees(Request $request) {
        try {
            // Check if user is authenticated
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            
            // Validate request method
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $userId = $_SESSION['user_id'];
            
            // Get user's Z-Score data
            $zScoreData = $this->zScoreModel->findByUserId($userId);
            
            if (!$zScoreData) {
                $this->sendJsonResponse(false, 'No Z-Score found. Please enter your Z-Score first.', null, 404);
                return;
            }
            
            // Extract data
            $zScore = $zScoreData['z_score'];
            $stream = $zScoreData['stream'];
            $district = $zScoreData['district'];
            $subject1 = $zScoreData['subject1'] ?? '';
            $subject2 = $zScoreData['subject2'] ?? '';
            $subject3 = $zScoreData['subject3'] ?? '';
            
            // Build Python script path (absolute path)
            $scriptPath = dirname(__DIR__) . '/python/eligibility.py';
            
            // Check if script exists
            if (!file_exists($scriptPath)) {
                $this->sendJsonResponse(false, 'Eligibility script not found at: ' . $scriptPath, null, 500);
                return;
            }
            
            // Use the virtual environment python which has mysql-connector installed
            $pythonPath = dirname(__DIR__) . '/.venv/bin/python';
            
            // Fallback to system python3 if virtual environment doesn't exist
            if (!file_exists($pythonPath)) {
                $pythonPath = '/opt/homebrew/bin/python3'; // macOS Homebrew path
                if (!file_exists($pythonPath)) {
                    $pythonPath = trim(shell_exec('which python3'));
                    if (empty($pythonPath)) {
                        $pythonPath = 'python3'; // Fallback to PATH
                    }
                }
            }
            
            // Build command with escaped arguments
            $command = escapeshellarg($pythonPath) . ' ' . 
                       escapeshellarg($scriptPath) . ' ' .
                       escapeshellarg($zScore) . ' ' .
                       escapeshellarg($stream) . ' ' .
                       escapeshellarg($district) . ' ' .
                       escapeshellarg($subject1) . ' ' .
                       escapeshellarg($subject2) . ' ' .
                       escapeshellarg($subject3) . ' 2>&1';
            
            // Log command for debugging
            error_log("Executing command: " . $command);
            
            // Execute Python script
            $output = shell_exec($command);
            
            // Log output for debugging
            error_log("Python output: " . $output);
            
            // Check if command executed
            if ($output === null) {
                $this->sendJsonResponse(false, 'Failed to execute eligibility script', null, 500);
                return;
            }
            
            // Extract only the JSON part (last line should be JSON)
            $lines = explode("\n", trim($output));
            $jsonLine = end($lines);
            
            // Try to decode JSON output
            $result = json_decode($jsonLine, true);
            
            // Check if JSON is valid
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Log the raw output for debugging
                error_log("JSON decode error: " . json_last_error_msg());
                error_log("Full Python output: " . $output);
                $this->sendJsonResponse(false, 'Invalid response from eligibility script: ' . json_last_error_msg(), [
                    'raw_output' => $output,
                    'json_line' => $jsonLine ?? null
                ], 500);
                return;
            }
            
            // Return the results
            $this->sendJsonResponse(true, 'Eligible programs found', $result);
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Validate Z-Score data
     */
    private function validateZScoreData($data) {
        $requiredFields = ['district', 'stream', 'subject1', 'subject2', 'subject3', 'zScore'];
        
        // Check required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                $this->sendJsonResponse(false, "Field '{$field}' is required", null, 400);
                return false;
            }
        }
        
        // Validate Z-Score range
        $zScore = floatval($data['zScore']);
        if ($zScore < 0 || $zScore > 3.0) {
            $this->sendJsonResponse(false, 'Z-Score must be between 0 and 3.0', null, 400);
            return false;
        }
        
        // Validate Z-Score decimal places
        $decimalPlaces = (strpos($data['zScore'], '.') !== false) 
            ? strlen(substr($data['zScore'], strpos($data['zScore'], '.') + 1)) 
            : 0;
        if ($decimalPlaces > 4) {
            $this->sendJsonResponse(false, 'Z-Score can have maximum 4 decimal places', null, 400);
            return false;
        }
        
        // Sanitize and prepare data
        return [
            'district' => trim($data['district']),
            'stream' => trim($data['stream']),
            'subject1' => trim($data['subject1']),
            'subject2' => trim($data['subject2']),
            'subject3' => trim($data['subject3']),
            'z_score' => $zScore
        ];
    }
    
    /**
     * Send JSON response
     */
    private function sendJsonResponse($success, $message, $data = null, $httpCode = 200) {
        http_response_code($httpCode);
        header('Content-Type: application/json');
        
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response);
        exit;
    }
}
