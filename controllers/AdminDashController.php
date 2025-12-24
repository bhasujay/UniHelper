<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/DegreeProgram.php';

use app\core\Request;
use app\models\DegreeProgram;


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
    
    // Add a new degree program
    public function addDegreeProgram(Request $request)
    {
        // Get form data
        $university_id = $request->get('university');
        $major_id = $request->get('major');
        $name = $request->get('degreeName');
        $stream = $request->get('stream');
        $unicode = $request->get('unicode');
        $duration = $request->get('duration');
        $descriptions = $request->get('description');
        
        // Create data array for the model
        $data = [
            'name' => $name,
            'university_id' => $university_id,
            'stream' => $stream,
            'unicode' => $unicode,
            'major_id' => $major_id,
            'descriptions' => $descriptions,
            'duration' => $duration
        ];
        
        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Add the degree
        $degreeModel->addDegree($data);
        
        // Redirect to degree programs management page
        header('Location: /unihelper/dashboard/admin/degree-programs-management');
        exit;
    }
    
    // Remove a degree program
    public function removeDegreeProgram($params)
    {
        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Check if $id is an array and extract the actual ID value
        $programId = is_array($params) ? $params['id'] : $params;
        
        error_log("Removing degree program with ID: " . $programId);
        
        // Delete the degree
        $result = $degreeModel->deleteDegree($programId);
        
        // Redirect to degree programs management page
        header('Location: /unihelper/dashboard/admin/degree-programs-management');
        exit;
    }
    
    // Update a degree program
    public function updateDegreeProgramForm($id, $request = null)
    {
        // Ensure $id is a number
        $programId = is_array($id) ? $id['id'] : intval($id);
        
        // Always use $_POST directly since the Request object appears to be empty
        $university_id = $_POST['university'] ?? null;
        $major_id = $_POST['major'] ?? null;
        $name = $_POST['degreeName'] ?? null;
        $stream = $_POST['stream'] ?? null;
        $unicode = $_POST['unicode'] ?? null;
        $duration = $_POST['duration'] ?? null;
        $descriptions = $_POST['description'] ?? null;
        
        error_log("Updating degree program ID: " . $programId);
        error_log("POST data: " . print_r($_POST, true));

        // Create data array for the model
        $data = [
            'name' => $name,
            'university_id' => $university_id,
            'stream' => $stream,
            'unicode' => $unicode,
            'major_id' => $major_id,
            'descriptions' => $descriptions,
            'duration' => $duration
        ];
        
        // Create instance of DegreeProgram model
        $degreeModel = new DegreeProgram();
        
        // Update the degree
        $result = $degreeModel->updateDegree($programId, $data);
        
        // Redirect to degree programs management page
        header('Location: /unihelper/dashboard/admin/degree-programs-management');
        exit;
    }
    
    // Get degree program data for editing
    public function getDegreeProgramData($id)
    {
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