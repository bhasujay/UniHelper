<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/ProgramModel.php';

use app\models\ProgramModel;
use app\core\Request;

/**
 * Controller for Degree Program operations
 * Handles searching, filtering, and retrieving degree programs
 */
class ProgramController
{
    private $programModel;
    
    public function __construct() {
        $this->programModel = new ProgramModel();
    }
    
    /**
     * Search degree programs
     * GET /api?controller=ProgramController&action=searchPrograms
     */
    public function searchPrograms(Request $request) {
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
                'unicode_code' => $_GET['unicode_code'] ?? null,
                'limit' => $_GET['limit'] ?? 20,
                'offset' => $_GET['offset'] ?? 0
            ];
            
            // Remove empty filters
            $filters = array_filter($filters, function($value) {
                return $value !== null && $value !== '';
            });
            
            $programs = $this->programModel->searchPrograms($searchTerm, $filters);
            
            $this->sendJsonResponse(true, 'Programs retrieved successfully', $programs);
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get search filters (universities, majors)
     * GET /api?controller=ProgramController&action=getSearchFilters
     */
    public function getSearchFilters(Request $request) {
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
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
    }
    
    /**
     * Get autocomplete suggestions
     * GET /api?controller=ProgramController&action=getAutocomplete
     */
    public function getAutocomplete(Request $request) {
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
            
        } catch (\Exception $e) {
            $this->sendJsonResponse(false, 'Server error: ' . $e->getMessage(), null, 500);
        }
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
