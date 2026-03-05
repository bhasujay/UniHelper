<?php

namespace app\controllers;

// Require the Feedback model
require_once dirname(__DIR__) . '/models/Feedback.php';

use app\models\Feedback;
use app\core\Request;

/**
 * FeedbackController
 * 
 * Handles all feedback-related operations including:
 * - Receiving and validating feedback submissions
 * - Sanitizing user input to prevent XSS and SQL injection
 * - Storing feedback in the database using prepared statements
 * - Returning JSON responses for AJAX requests
 */
class FeedbackController
{
    /**
     * Submit feedback via AJAX
     * 
     * This method:
     * 1. Receives POST data from the feedback form
     * 2. Validates the input data (server-side validation)
     * 3. Sanitizes data to prevent XSS attacks
     * 4. Uses prepared statements to prevent SQL injection
     * 5. Inserts data into the database
     * 6. Returns JSON response with success/error status
     * 
     * @param Request $request The HTTP request object
     * @return string JSON response - {"success": true/false, "message": "...", "data": {...}}
     */
    public function submitFeedback(Request $request)
    {
        // Set JSON response header
        header('Content-Type: application/json');
        
        try {
            // Verify request method is POST
            if ($request->getMethod() !== 'POST') {
                http_response_code(405);
                return json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed. Only POST requests are accepted.'
                ]);
            }
            
            // Get POST data from request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Check if JSON decoding failed
            if ($input === null) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON format in request body'
                ]);
            }
            
            // STEP 1: Extract form fields
            $name = $input['name'] ?? '';
            $title = $input['title'] ?? '';
            $message = $input['message'] ?? '';
            
            // STEP 2: Server-side validation (additional layer of security)
            $validationErrors = $this->validateFeedback($name, $title, $message);
            if (!empty($validationErrors)) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validationErrors),
                    'errors' => $validationErrors
                ]);
            }
            
            // STEP 3: Sanitize input data
            // This prevents XSS by escaping special characters
            // but preserves the original data content (no modification of values)
            $sanitizedName = $this->sanitizeInput($name);
            $sanitizedTitle = $this->sanitizeInput($title);
            $sanitizedMessage = $this->sanitizeInput($message);
            
            // STEP 4: Insert into database using prepared statements (prevents SQL injection)
            $feedbackModel = new Feedback();
            $insertResult = $feedbackModel->insertFeedback(
                $sanitizedName,
                $sanitizedTitle,
                $sanitizedMessage
            );
            
            if ($insertResult['success']) {
                http_response_code(201);
                return json_encode([
                    'success' => true,
                    'message' => 'Feedback submitted successfully!',
                    'data' => [
                        'id' => $insertResult['id'],
                        'name' => $sanitizedName,
                        'title' => $sanitizedTitle,
                        'created_at' => $insertResult['created_at']
                    ]
                ]);
            } else {
                http_response_code(500);
                return json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $insertResult['error']
                ]);
            }
            
        } catch (\PDOException $e) {
            // Handle database connection errors
            http_response_code(500);
            return json_encode([
                'success' => false,
                'message' => 'Database connection error. Please try again later.'
            ]);
        } catch (\Exception $e) {
            // Handle unexpected errors
            http_response_code(500);
            return json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred.'
            ]);
        }
    }
    
    /**
     * Server-side validation of feedback data
     * 
     * Validates:
     * - Name: not empty, minimum 2 characters, maximum 100 characters
     * - Title: not empty, minimum 5 characters, maximum 255 characters
     * - Message: not empty, minimum 10 characters, maximum 5000 characters
     * 
     * @param string $name User's name
     * @param string $title Feedback title
     * @param string $message Feedback message
     * @return array Array of validation error messages (empty if valid)
     */
    private function validateFeedback($name, $title, $message)
    {
        $errors = [];
        
        // Validate name
        if (empty(trim($name))) {
            $errors[] = 'Name is required';
        } else if (strlen(trim($name)) < 2) {
            $errors[] = 'Name must be at least 2 characters';
        } else if (strlen(trim($name)) > 100) {
            $errors[] = 'Name must not exceed 100 characters';
        }
        
        // Validate title
        if (empty(trim($title))) {
            $errors[] = 'Title is required';
        } else if (strlen(trim($title)) < 5) {
            $errors[] = 'Title must be at least 5 characters';
        } else if (strlen(trim($title)) > 255) {
            $errors[] = 'Title must not exceed 255 characters';
        }
        
        // Validate message
        if (empty(trim($message))) {
            $errors[] = 'Message is required';
        } else if (strlen(trim($message)) < 10) {
            $errors[] = 'Message must be at least 10 characters';
        } else if (strlen(trim($message)) > 5000) {
            $errors[] = 'Message must not exceed 5000 characters';
        }
        
        return $errors;
    }
    
    /**
     * Sanitize user input to prevent XSS attacks
     * 
     * This function:
     * 1. Trims whitespace
     * 2. Escapes HTML special characters
     * 3. Preserves the content (does not modify the values)
     * 
     * The sanitized data can be safely displayed in HTML or used in the database.
     * For database storage, prepared statements provide additional SQL injection protection.
     * 
     * @param string $input Raw user input
     * @return string Sanitized input safe for HTML display and database storage
     */
    private function sanitizeInput($input)
    {
        // Trim whitespace from both ends
        $trimmed = trim($input);
        
        // Escape HTML special characters to prevent XSS
        // ENT_QUOTES: escapes double quotes, single quotes, and other special chars
        // UTF-8: specify character encoding
        $escaped = htmlspecialchars($trimmed, ENT_QUOTES, 'UTF-8');
        
        return $escaped;
    }

    /**
     * Get all feedback posts
     * 
     * Retrieves all feedback from the database for display
     * 
     * @return string JSON response with all feedback posts
     */
    public function getFeedback()
    {
        header('Content-Type: application/json');
        
        try {
            $feedbackModel = new Feedback();
            $feedbackList = $feedbackModel->getAllFeedback(100, 0); // Get up to 100 posts
            
            http_response_code(200);
            return json_encode([
                'success' => true,
                'data' => $feedbackList
            ]);
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'success' => false,
                'message' => 'Failed to retrieve feedback: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Update feedback post
     * 
     * Updates an existing feedback post by ID
     * 
     * @param Request $request The HTTP request object
     * @return string JSON response
     */
    public function updateFeedback(Request $request)
    {
        header('Content-Type: application/json');
        
        try {
            if ($request->getMethod() !== 'PUT') {
                http_response_code(405);
                return json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed. Only PUT requests are accepted.'
                ]);
            }
            
            // Get PUT data from request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($input === null) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON format in request body'
                ]);
            }
            
            $id = $input['id'] ?? null;
            $name = $input['name'] ?? '';
            $title = $input['title'] ?? '';
            $message = $input['message'] ?? '';
            
            // Validate ID
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid feedback ID'
                ]);
            }
            
            // Validate feedback data
            $validationErrors = $this->validateFeedback($name, $title, $message);
            if (!empty($validationErrors)) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Validation failed: ' . implode(', ', $validationErrors),
                    'errors' => $validationErrors
                ]);
            }
            
            // Sanitize input
            $sanitizedName = $this->sanitizeInput($name);
            $sanitizedTitle = $this->sanitizeInput($title);
            $sanitizedMessage = $this->sanitizeInput($message);
            
            // Update in database
            $feedbackModel = new Feedback();
            $updateResult = $feedbackModel->updateFeedback(
                $id,
                $sanitizedName,
                $sanitizedTitle,
                $sanitizedMessage
            );
            
            if ($updateResult['success']) {
                http_response_code(200);
                return json_encode([
                    'success' => true,
                    'message' => 'Feedback updated successfully!',
                    'data' => [
                        'id' => $id,
                        'name' => $sanitizedName,
                        'title' => $sanitizedTitle,
                        'message' => $sanitizedMessage
                    ]
                ]);
            } else {
                http_response_code(500);
                return json_encode([
                    'success' => false,
                    'message' => 'Database error: ' . $updateResult['error']
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred.'
            ]);
        }
    }

    /**
     * Delete feedback post
     * 
     * Deletes a feedback post by ID
     * 
     * @param Request $request The HTTP request object
     * @return string JSON response
     */
    public function deleteFeedback(Request $request)
    {
        header('Content-Type: application/json');
        
        try {
            if ($request->getMethod() !== 'DELETE') {
                http_response_code(405);
                return json_encode([
                    'success' => false,
                    'message' => 'Method Not Allowed. Only DELETE requests are accepted.'
                ]);
            }
            
            // Get DELETE data from request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($input === null) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid JSON format in request body'
                ]);
            }
            
            $id = $input['id'] ?? null;
            
            // Validate ID
            if (!$id || !is_numeric($id)) {
                http_response_code(400);
                return json_encode([
                    'success' => false,
                    'message' => 'Invalid feedback ID'
                ]);
            }
            
            // Delete from database
            $feedbackModel = new Feedback();
            $deleteResult = $feedbackModel->deleteFeedback($id);
            
            if ($deleteResult['success']) {
                http_response_code(200);
                return json_encode([
                    'success' => true,
                    'message' => 'Feedback deleted successfully!'
                ]);
            } else {
                http_response_code(500);
                return json_encode([
                    'success' => false,
                    'message' => $deleteResult['message'] ?? 'Failed to delete feedback'
                ]);
            }
        } catch (\Exception $e) {
            http_response_code(500);
            return json_encode([
                'success' => false,
                'message' => 'An unexpected error occurred.'
            ]);
        }
    }
}

