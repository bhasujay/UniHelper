<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/controllers/DashboardController.php';
require_once dirname(__DIR__, 1) . '/models/DegreeProgram.php';
require_once dirname(__DIR__, 1) . '/models/University.php';
require_once dirname(__DIR__, 1) . '/models/Major.php';

use app\core\Request;
use app\models\DegreeProgram;
use app\models\Major;
use app\models\University;


// Controller for the admin dashboard
class AdminDashController extends DashboardController
{
    protected $validComponents = [
        'role-applications',
        'degree-programs-management',
        'content-review-queue'
    ];
    
    protected $dashboardTemplate = 'dashboard_adm.php';
    protected $defaultComponent = 'degree-programs-management';
    protected $requiredRole = 'role-admin';

    private function ensureAdminAccess(): void
    {
        if (($this->user->role ?? '') !== $this->requiredRole) {
            http_response_code(403);
            echo "<div class='error'>Access denied</div>";
            exit;
        }
    }

    private function redirectToManagement(): void
    {
        header('Location: /unihelper/degree-programs-management');
        exit;
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
    
    // Add a new degree program
    public function addDegreeProgram(Request $request)
    {
        $this->ensureAdminAccess();
        unset($request);
        
        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Add the degree
        $degreeModel->addDegree($this->buildDegreePayloadFromPost());
        
        // Redirect to degree programs management page
        $this->redirectToManagement();
    }
    
    // Remove a degree program
    public function removeDegreeProgram($params)
    {
        $this->ensureAdminAccess();

        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Check if $id is an array and extract the actual ID value
        $programId = is_array($params) ? $params['id'] : $params;
        
        error_log("Removing degree program with ID: " . $programId);
        
        // Delete the degree
        $result = $degreeModel->deleteDegree($programId);
        unset($result);
        
        // Redirect to degree programs management page
        $this->redirectToManagement();
    }
    
    // Update a degree program
    public function updateDegreeProgramForm($id, $request = null)
    {
        $this->ensureAdminAccess();

        // Ensure $id is a number
        $programId = is_array($id) ? $id['id'] : intval($id);
        unset($request);
        
        error_log("Updating degree program ID: " . $programId);
        error_log("POST data: " . print_r($_POST, true));
        
        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Update the degree
        $result = $degreeModel->updateDegree($programId, $this->buildDegreePayloadFromPost());
        unset($result);
        
        // Redirect to degree programs management page
        $this->redirectToManagement();
    }
    
    // Get degree program data for editing
    public function getDegreeProgramData($id)
    {
        $this->ensureAdminAccess();

        // Ensure $id is a number
        $programId = is_array($id) ? $id['id'] : intval($id);
        
        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Get the degree data
        $degreeData = $degreeModel->getDegreeById($programId);
        
        // Return as JSON
        header('Content-Type: application/json');
        echo json_encode($degreeData);
        exit;
    }
}