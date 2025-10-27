<?php

namespace app\controllers;

use app\models\ZScoreModel;
use app\models\ProgramModel;
use app\models\WishlistModel;

/**
 * Controller for the university applicant dashboard
 */
class ApplicantDashController extends DashboardController
{
    protected $validComponents = [
        'z-score-checker',
        'degree-programs',
        'find-applicant',
        'unicode-generator',
        'qa-forum',
        'connect-undergrads'
    ];
    
    protected $dashboardTemplate = 'dashboard_app.php';
    protected $defaultComponent = 'qa-forum';
    protected $requiredRole = 'role-applicant';
    
    private $zScoreModel;
    private $programModel;
    private $wishlistModel;
    
    public function __construct() {
        parent::__construct();
        $this->zScoreModel = new ZScoreModel();
        $this->programModel = new ProgramModel();
        $this->wishlistModel = new WishlistModel();
    }
    
    /**
     * Save or update user's Z-Score
     * POST /api/z-score/save
     */
    public function saveZScore() {
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
            
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get user's Z-Score
     * GET /api/z-score/get
     */
    public function getZScore() {
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
            
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    
    /**
     * Delete user's Z-Score
     * DELETE /api/z-score/delete
     */
    public function deleteZScore() {
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
            
        } catch (Exception $e) {
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
        $decimalPlaces = (strpos($data['zScore'], '.') !== false) ? strlen(substr($data['zScore'], strpos($data['zScore'], '.') + 1)) : 0;
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
    
    /**
     * Search degree programs
     * GET /api/programs/search
     */
    public function searchPrograms() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            // Get search parameters
            $searchTerm = $_GET['q'] ?? '';
            $filters = [
                'university_id' => $_GET['university_id'] ?? null,
                'stream' => $_GET['stream'] ?? null,
                'major_id' => $_GET['major_id'] ?? null,
                'unicode' => $_GET['unicode'] ?? null,
                'limit' => $_GET['limit'] ?? 20,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $programs = $this->programModel->searchPrograms($searchTerm, $filters);
            
            $this->sendJsonResponse(true, 'Programs retrieved successfully', $programs);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get search filters (universities, majors)
     * GET /api/programs/filters
     */
    public function getSearchFilters() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $universities = $this->programModel->getAllUniversities();
            $majors = $this->programModel->getAllMajors();
            
            $filters = [
                'universities' => $universities,
                'majors' => $majors
            ];
            
            $this->sendJsonResponse(true, 'Filters retrieved successfully', $filters);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get autocomplete suggestions
     * GET /api/programs/autocomplete
     */
    public function getAutocomplete() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            
            $term = $_GET['term'] ?? '';
            if (strlen($term) < 2) {
                $this->sendJsonResponse(true, 'No suggestions', []);
                return;
            }
            
            $suggestions = $this->programModel->getAutocompleteSuggestions($term);
            $this->sendJsonResponse(true, 'Suggestions retrieved', $suggestions);
            
        } catch (Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Check if a program is in the user's wishlist
     * GET /api/wishlist/check?program_id=ID
     */
    public function checkWishlist() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            $userId = $_SESSION['user_id'];
            $programId = isset($_GET['program_id']) ? intval($_GET['program_id']) : 0;
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }
            $inWishlist = $this->wishlistModel->isInWishlist($userId, $programId);
            $this->sendJsonResponse(true, 'Wishlist status retrieved', [ 'isInWishlist' => $inWishlist ]);
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Add a program to the user's wishlist
     * POST /api/wishlist/add
     */
    public function addToWishlist() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $programId = isset($input['program_id']) ? intval($input['program_id']) : 0;
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }
            $userId = $_SESSION['user_id'];
            $this->wishlistModel->addToWishlist($userId, $programId);
            $this->sendJsonResponse(true, 'Added to wishlist');
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Remove a program from the user's wishlist
     * DELETE /api/wishlist/remove
     */
    public function removeFromWishlist() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
                $this->sendJsonResponse(false, 'Method not allowed', null, 405);
                return;
            }
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
            $programId = isset($input['program_id']) ? intval($input['program_id']) : 0;
            if ($programId <= 0) {
                $this->sendJsonResponse(false, 'Invalid program_id', null, 400);
                return;
            }
            $userId = $_SESSION['user_id'];
            $removed = $this->wishlistModel->removeFromWishlist($userId, $programId);
            if ($removed) {
                $this->sendJsonResponse(true, 'Removed from wishlist');
            } else {
                $this->sendJsonResponse(false, 'Item not found in wishlist', null, 404);
            }
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get wishlist count for the current user
     * GET /api/wishlist/count
     */
    public function getWishlistCount() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            $userId = $_SESSION['user_id'];
            $count = $this->wishlistModel->getWishlistCount($userId);
            $this->sendJsonResponse(true, 'Wishlist count retrieved', ['count' => $count]);
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }

    /**
     * Get wishlist items for the current user
     * GET /api/wishlist/items
     */
    public function getWishlistItems() {
        try {
            if (!isset($_SESSION['user_id'])) {
                $this->sendJsonResponse(false, 'User not authenticated', null, 401);
                return;
            }
            $userId = $_SESSION['user_id'];
            $items = $this->wishlistModel->getUserWishlist($userId);
            $this->sendJsonResponse(true, 'Wishlist items retrieved', $items);
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
}