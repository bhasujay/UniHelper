<?php

namespace app\controllers;

use app\core\Application;
use app\core\Request;
use app\models\User;

class ProfileController
{
    private $user;

    public function __construct()
    {
        // Check if user is logged in
        session_start();
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

    // Show profile page
    public function index()
    {
        return $this->render('profile/index.php', [
            'user' => $this->user
        ]);
    }

    // Show edit profile form
    public function edit()
    {
        return $this->render('profile/edit.php', [
            'user' => $this->user
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
            } elseif ($this->user->role === 'role-undergrad') {
                $this->user->undergradUniversity = $request->get('undergradUniversity');
                $this->user->major = $request->get('major');
            } elseif ($this->user->role === 'role-profile') {
                $this->user->profileUniversity = $request->get('profileUniversity');
                $this->user->profileRole = $request->get('profileRole');
            }

            // Handle profile picture upload
            $this->handleProfilePicture($request);

            // Validate data
            $errors = $this->user->validate();
            
            if (!empty($errors)) {
                return $this->render('profile/edit.php', [
                    'user' => $this->user,
                    'error' => implode(', ', $errors)
                ]);
            }

            // Update user
            if ($this->user->update()) {
                return $this->render('profile/edit.php', [
                    'user' => $this->user,
                    'success' => 'Profile updated successfully'
                ]);
            } else {
                return $this->render('profile/edit.php', [
                    'user' => $this->user,
                    'error' => 'Failed to update profile'
                ]);
            }
        }

        return $this->render('profile/edit.php', [
            'user' => $this->user
        ]);
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
                    mkdir($uploadPath, 0777, true);
                }
                
                // Upload file
                $destination = $uploadPath . $newFileName;
                if (move_uploaded_file($fileTmpName, $destination)) {
                    // Remove existing file if it exists
                    if ($this->user->profilePicture && file_exists(Application::$ROOT_DIR . '/public' . $this->user->profilePicture)) {
                        unlink(Application::$ROOT_DIR . '/public' . $this->user->profilePicture);
                    }
                    
                    // Update user profile picture path
                    $this->user->profilePicture = $uploadDir . $newFileName;
                }
            }
        }
    }

    protected function render($view, $data = [])
    {
        // Extract data array to variables
        extract($data);
        require Application::$ROOT_DIR . "/views/$view";
    }
}