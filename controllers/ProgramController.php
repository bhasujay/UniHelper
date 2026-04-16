<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/controllers/DashboardController.php';
require_once dirname(__DIR__) . '/models/base-model.php';
require_once dirname(__DIR__) . '/models/ProgramModel.php';
require_once dirname(__DIR__) . '/models/DegreeProgram.php';
require_once dirname(__DIR__) . '/models/University.php';
require_once dirname(__DIR__) . '/models/Major.php';

use app\core\Request;
use app\models\DegreeProgram;
use app\models\Major;
use app\models\ProgramModel;
use app\models\University;

/**
 * Controller for Degree Program operations
 * Handles searching, filtering, and retrieving degree programs
 */
class ProgramController extends DashboardController
{
    private $programModel;
    
    public function __construct() {
        parent::__construct();
        $this->programModel = new ProgramModel();
    }

    private function ensureAdminAccess(bool $asJson = false): void
    {
        if (($this->user->role ?? '') !== 'role-admin') {
            http_response_code(403);

            if ($asJson) {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => 'Access denied.'
                ]);
            } else {
                echo "<div class='error'>Access denied</div>";
            }

            exit;
        }
    }

    private function buildDegreePayloadFromPost(): array
    {
        return [
            'name' => trim((string)($_POST['degreeName'] ?? '')),
            'university_id' => $_POST['university'] ?? null,
            'stream' => trim((string)($_POST['stream'] ?? '')),
            'unicode' => trim((string)($_POST['unicode'] ?? '')),
            'major_id' => $_POST['major'] ?? null,
            'descriptions' => trim((string)($_POST['description'] ?? '')),
            'duration' => trim((string)($_POST['duration'] ?? '')),
            'path_description' => trim((string)($_POST['pathDescription'] ?? 'Default Entry Path')),
            'subject_requirements' => (string)($_POST['subjectRequirements'] ?? '')
        ];
    }

    /**
     * Render admin degree management SSR page
     * GET /degree-programs-management
     */
    public function degreeProgramsManagement()
    {
        $this->ensureAdminAccess();

        $this->activeComponent = 'degree-programs-management';

        $majorModel = new Major();
        $universityModel = new University();
        $degreeModel = new DegreeProgram();

        $content = $this->loadComponent('degree-programs-management', [
            'universities' => $universityModel->getAll(),
            'majors' => $majorModel->getAll(),
            'degrees' => $degreeModel->getAllDegrees()
        ]);

        return $this->renderDashboard($content, $this->role_data[$this->user->role]);
    }

    /**
     * Add new degree program
     * POST /api?controller=ProgramController&action=addDegreeProgram
     */
    public function addDegreeProgram(Request $request)
    {
        $this->ensureAdminAccess(true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Method not allowed', null, 405);
        }

        $degreeModel = new DegreeProgram();
        $programId = $degreeModel->addDegree($this->buildDegreePayloadFromPost());

        if ($programId === false) {
            $this->sendJsonResponse(false, 'Failed to add degree program.', null, 500);
        }

        $this->sendJsonResponse(true, 'Degree program added successfully.', [
            'program_id' => (int)$programId
        ]);
    }

    /**
     * Remove a degree program
     * POST /api?controller=ProgramController&action=removeDegreeProgram&id=:id
     */
    public function removeDegreeProgram(Request $request)
    {
        $this->ensureAdminAccess(true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Method not allowed', null, 405);
        }

        $programId = (int)($request->get('id') ?? 0);
        if ($programId <= 0) {
            $this->sendJsonResponse(false, 'Invalid degree program ID.', null, 400);
        }

        $degreeModel = new DegreeProgram();
        $deleted = $degreeModel->deleteDegree($programId);

        if (!$deleted) {
            $this->sendJsonResponse(false, 'Failed to delete degree program.', null, 500);
        }

        $this->sendJsonResponse(true, 'Degree program deleted successfully.');
    }

    /**
     * Update an existing degree program
     * POST /api?controller=ProgramController&action=updateDegreeProgramForm&id=:id
     */
    public function updateDegreeProgramForm(Request $request)
    {
        $this->ensureAdminAccess(true);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->sendJsonResponse(false, 'Method not allowed', null, 405);
        }

        $programId = (int)($request->get('id') ?? 0);
        if ($programId <= 0) {
            $this->sendJsonResponse(false, 'Invalid degree program ID.', null, 400);
        }

        $degreeModel = new DegreeProgram();
        $updated = $degreeModel->updateDegree($programId, $this->buildDegreePayloadFromPost());

        if (!$updated) {
            $this->sendJsonResponse(false, 'Failed to update degree program.', null, 500);
        }

        $this->sendJsonResponse(true, 'Degree program updated successfully.', [
            'program_id' => $programId
        ]);
    }

    /**
     * Get one degree program for edit mode
     * GET /api?controller=ProgramController&action=getDegreeProgramData&id=:id
     */
    public function getDegreeProgramData(Request $request)
    {
        $this->ensureAdminAccess(true);

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->sendJsonResponse(false, 'Method not allowed', null, 405);
        }

        $programId = (int)($request->get('id') ?? 0);
        if ($programId <= 0) {
            $this->sendJsonResponse(false, 'Invalid degree program ID.', null, 400);
        }

        $degreeModel = new DegreeProgram();
        $degreeData = $degreeModel->getDegreeById($programId);

        if ($degreeData === null) {
            $this->sendJsonResponse(false, 'Degree program not found.', null, 404);
        }

        $this->sendJsonResponse(true, 'Degree program retrieved successfully.', $degreeData);
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
