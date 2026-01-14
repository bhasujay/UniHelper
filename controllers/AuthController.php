<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/University.php';
require_once dirname(__DIR__, 1) . '/models/Major.php';
require_once dirname(__DIR__, 1) . '/models/User.php';

use app\core\Application;
use app\core\Request;
use app\models\User;
use app\models\University;
use app\models\Major;

class AuthController
{
    // Show login form
    public function login(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            // Process login
            $email = $request->get('email');
            $hashedPassword = $request->get('hashed_password');

            if (empty($email) || empty($hashedPassword)) {
                return $this->render('login.php', ['error' => 'Email and password are required']);
            }

            // Find user by email
            $user = new User();
            $foundUser = $user->findByEmail($email);

            if (!$foundUser) {
                return $this->render('login.php', ['error' => 'Invalid email or password']);
            }

            // Verify the password using the same method as registration
            $passwordVerified = hash_equals($hashedPassword, $foundUser->password_hash);
            
            if ($foundUser && $passwordVerified) {
                // Login successful
                session_start();
                $_SESSION['user_id'] = $foundUser->id;
                $_SESSION['user_email'] = $foundUser->email;
                $_SESSION['user_role'] = $foundUser->role;
                $_SESSION['user_name'] = $foundUser->firstName . ' ' . $foundUser->lastName;

                // Redirect based on role
                header('Location: /UniHelper/dashboard');
                exit;
            } else {
                return $this->render('login.php', ['error' => 'Invalid email or password']);
            }
        }

        return $this->render('login.php');
    }

    // Show register form
    public function register(Request $request)
    {
        if ($request->getMethod() === 'POST') {
            // Process registration
            $user = new User();
            
            // Get form data
            $user->firstName = $request->get('firstName');
            $user->lastName = $request->get('lastName');
            $user->email = $request->get('email');
            $user->phone = $request->get('phone');
            $user->role = $request->get('userRole');
            
            // Role-specific fields
            if ($user->role === 'role-applicant') {
                $user->alYear = $request->get('alYear');
            } elseif ($user->role === 'role-undergrad') {
                $user->University = $request->get('undergradUniversity');
                $user->major = $request->get('major');
            } elseif ($user->role === 'role-profile') {
                $user->University = $request->get('profileUniversity');
                $user->profileRole = $request->get('role');
            }

            // Handle password (already hashed by JavaScript)
            $user->password_hash = $request->get('hashed_password');

            // Validate data
            $errors = $user->validate();

            // Handle profile picture upload
            $profilePicture = $request->get('profilePicture');
            if ($profilePicture && isset($profilePicture['tmp_name']) && is_uploaded_file($profilePicture['tmp_name'])) {
                $emailUsername = explode('@', $user->email)[0];
                $targetFile = Application::$ROOT_DIR . '/public/uploads/profilePictures/' . $emailUsername . '.' . pathinfo($profilePicture['name'], PATHINFO_EXTENSION);

                if (move_uploaded_file($profilePicture['tmp_name'], $targetFile)) {
                    $user->profilePicture = '/uploads/profilePictures/' . basename($targetFile);
                } else {
                    $errors[] = "Failed to upload profile picture.";
                }
            }

            if (!empty($errors)) {
                return $this->render('register.php', ['errors' => $errors]);
            }

            // Check if email already exists
            if ($user->emailExists($user->email)) {
                return $this->render('register.php', ['error' => 'Email already exists']);
            }

            // Save user
            if ($user->save()) {
                // Registration successful
                session_start();
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['user_name'] = $user->firstName . ' ' . $user->lastName;

                // Redirect based on role
                header('Location: /UniHelper/dashboard');
                exit;
            } else {
                return $this->render('register.php', ['error' => "Registration failed. Please try again."]);
            }
        }

        return $this->render('register.php');
    }

    // Populate register form
    public function populateRegisterForm()
    {
        $universityModel = new University();
        $majorModel = new Major();

        $universities = $universityModel->getAll();
        $majors = $majorModel->getAll();

        return $this->render('register.php', [
            'universities' => $universities,
            'majors' => $majors
        ]);
    }

    // Logout user
    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: /UniHelper/home');
        exit;
    }

    // password change
    public function changePassword(Request $request)
    { 
    }

    // to reload the views with data/errors
    protected function render($view, $data = [])
    {
        // Extract data array to variables
        extract($data);
        require Application::$ROOT_DIR . "/views/$view";
    }

    // helper functions
    static function getUserNameById(Request $request)
    {
        $id = $request->get('id');
        $user = new User();
        $user = $user->findById($id);
        return $user->firstName . ' ' . $user->lastName;
    }
}
