<?php

namespace app\controllers;

use app\core\Application;
use app\core\Request;
use app\models\User;
use app\models\University;
use app\models\Major;

class ProfileController
{
    private $user;

    public function __construct()
    {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: /UniHelper/login');
            exit;
        }

        // Get the user from database
        $user = new User();
        $this->user = $user->findById($_SESSION['user_id']);
        
        if (!$this->user) {
            session_destroy();
            header('Location: /UniHelper/login');
            exit;
        }
    }

    // Load profile as a component
    public function component()
    {
        // Return the content to be loaded into the dashboard
        return $this->loadComponent('profile/profile-component.php', [
            'user' => $this->user
        ]);
    }
    
    // Load edit profile as a component
    public function editComponent()
    {
        // Get all universities and majors for dropdowns
        $universityModel = new University();
        $universities = $universityModel->getAll();
        
        $majorModel = new Major();
        $majors = $majorModel->getAll();
        
        return $this->loadComponent('profile/edit-component.php', [
            'user' => $this->user,
            'universities' => $universities,
            'majors' => $majors
        ]);
    }

    // Show profile page (for direct access)
    public function index()
    {
        // Determine which dashboard to use based on user role
        $dashboardTemplate = $this->getDashboardByRole($this->user->role);
        return $this->renderInDashboard($dashboardTemplate, 'profile/profile-component.php', [
            'user' => $this->user
        ]);
    }

    // Show edit profile form (for direct access)
    public function edit()
    {
        // Get all universities and majors for dropdowns
        $universityModel = new University();
        $universities = $universityModel->getAll();
        
        $majorModel = new Major();
        $majors = $majorModel->getAll();
        
        // Determine which dashboard to use based on user role
        $dashboardTemplate = $this->getDashboardByRole($this->user->role);
        return $this->renderInDashboard($dashboardTemplate, 'profile/edit-component.php', [
            'user' => $this->user,
            'universities' => $universities,
            'majors' => $majors
        ]);
    }

    // Update profile information
    public function update(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            // Get form data
            $this->user->firstName = $request->get('firstName');
            $this->user->lastName = $request->get('lastName');
            $this->user->email = $request->get('email');
            $this->user->phone = $request->get('phone');

            // Role-specific fields
            if ($this->user->role === 'role-applicant') {
                $this->user->alYear = $request->get('alYear');
                $this->user->University = null; // Reset these fields for applicants
                $this->user->major = null;
                $this->user->profileRole = null;
            } elseif ($this->user->role === 'role-undergrad') {
                $this->user->University = $request->get('undergradUniversity') ?: null;
                $this->user->major = $request->get('major') ?: null;
                $this->user->profileRole = null;
            } elseif ($this->user->role === 'role-profile') {
                $this->user->University = $request->get('profileUniversity') ?: null;
                $this->user->profileRole = $request->get('profileRole');
                $this->user->major = null;
            }

            // Handle profile picture upload
            $this->handleProfilePicture($request);

            // Validate data
            $errors = $this->user->validateProfileUpdate();
            
            // Get fresh university and major data for re-rendering the form
            $universityModel = new University();
            $universities = $universityModel->getAll();
            
            $majorModel = new Major();
            $majors = $majorModel->getAll();
            
            if (!empty($errors)) {
                return $this->edit([
                    'error' => implode(', ', $errors),
                    'universities' => $universities,
                    'majors' => $majors
                ]);
            }

            // Update user
            if ($this->user->update()) {
                return $this->edit([
                    'success' => 'Profile updated successfully',
                    'universities' => $universities,
                    'majors' => $majors
                ]);
            } else {
                return $this->edit([
                    'error' => 'Failed to update profile',
                    'universities' => $universities,
                    'majors' => $majors
                ]);
            }
        }

        return $this->edit();
    }

    // Change password
    public function changePassword(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            $currentPassword = $request->get('current_password');
            $newPassword = $request->get('new_password');
            $confirmPassword = $request->get('confirm_password');

            // Verify current password
            if (!$this->user->verifyPassword($currentPassword, $this->user->password_hash)) {
                return $this->render('profile/change-password.php', [
                    'user' => $this->user,
                    'error' => 'Current password is incorrect'
                ]);
            }

            // Verify new passwords match
            if ($newPassword !== $confirmPassword) {
                return $this->render('profile/change-password.php', [
                    'user' => $this->user,
                    'error' => 'New passwords do not match'
                ]);
            }

            // Update password
            $this->user->password_hash = $this->user->hashPassword($newPassword);
            
            if ($this->user->updatePassword()) {
                return $this->render('profile/change-password.php', [
                    'user' => $this->user,
                    'success' => 'Password updated successfully'
                ]);
            } else {
                return $this->render('profile/change-password.php', [
                    'user' => $this->user,
                    'error' => 'Failed to update password'
                ]);
            }
        }

        return $this->render('profile/change-password.php', [
            'user' => $this->user
        ]);
    }

    // Handle profile picture upload
    private function handleProfilePicture(Request $request)
    {
        // Check if user wants to remove profile picture
        if ($request->get('removeProfilePicture') === '1') {
            // Remove existing file if it exists
            if ($this->user->profilePicture && file_exists(Application::$ROOT_DIR . '/public' . $this->user->profilePicture)) {
                unlink(Application::$ROOT_DIR . '/public' . $this->user->profilePicture);
            }
            $this->user->profilePicture = null;
            return;
        }

        // Check if file was uploaded
        if (isset($_FILES['profilePicture']) && $_FILES['profilePicture']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['profilePicture'];
            $fileName = $file['name'];
            $fileTmpName = $file['tmp_name'];
            $fileError = $file['error'];
            
            // Get file extension
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            // Allowed extensions
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif'];
            
            // Check if extension is allowed
            if (in_array($fileExt, $allowedExts)) {
                // Generate unique file name
                $newFileName = uniqid('profile_', true) . '.' . $fileExt;
                // Upload directory
                $uploadDir = '/uploads/profiles/';
                $uploadPath = Application::$ROOT_DIR . '/public' . $uploadDir;
                
                // Create directory if it doesn't exist
                if (!is_dir($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }
                
                // Move uploaded file to target directory
                if (move_uploaded_file($fileTmpName, $uploadPath . $newFileName)) {
                    // Delete old file if it exists
                    if ($this->user->profilePicture && file_exists(Application::$ROOT_DIR . '/public' . $this->user->profilePicture)) {
                        unlink(Application::$ROOT_DIR . '/public' . $this->user->profilePicture);
                    }
                    $this->user->profilePicture = $uploadDir . $newFileName;
                } else {
                    // Handle error in moving uploaded file
                    throw new \Exception('Error in moving uploaded file');
                }
            } else {
                // Handle invalid file extension
                throw new \Exception('Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.');
            }
        }
    }

    // Determine dashboard template based on user role
    private function getDashboardByRole($role)
    {
        if (strpos($role, 'applicant') !== false) {
            return 'dashboard_app.php';
        } elseif (strpos($role, 'undergrad') !== false) {
            return 'dashboard_und.php';
        } elseif (strpos($role, 'profile') !== false) {
            return 'dashboard_pro.php';
        } else {
            return 'dashboard_app.php'; // Default fallback
        }
    }
    
    // Load component content
    private function loadComponent($view, $data = [])
    {
        // Extract data array to variables
        extract($data);
        ob_start();
        require Application::$ROOT_DIR . "/views/$view";
        return ob_get_clean();
    }
    
    // Render a component within the dashboard template
    private function renderInDashboard($dashboardTemplate, $componentView, $data = [])
    {
        $content = $this->loadComponent($componentView, $data);
        extract($data);
        require Application::$ROOT_DIR . "/views/$dashboardTemplate";
    }

    // For backward compatibility
    protected function render($view, $data = [])
    {
        extract($data);
        require Application::$ROOT_DIR . "/views/$view";
    }
}