<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/University.php';
require_once dirname(__DIR__, 1) . '/models/Major.php';
require_once dirname(__DIR__, 1) . '/models/User.php';
require_once dirname(__DIR__, 1) . '/models/Notify.php';
require_once dirname(__DIR__, 1) . '/models/user-stat.php';

use app\core\Application;
use app\core\Request;
use app\models\User;
use app\models\University;
use app\models\Major;
use app\models\Notify;
use app\models\UserStat;

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

            // Double-hash: server hashes the client's SHA-256 hash again
            $serverHash = hash('sha256', $hashedPassword);
            $passwordVerified = hash_equals($serverHash, $foundUser->password_hash);
            
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

            // Handle password: client sends SHA-256 hash, server hashes it again
            $clientHash = $request->get('hashed_password');
            $user->password_hash = hash('sha256', $clientHash);

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

            // Check if phone already exists
            if ($user->phoneExists($user->phone)) {
                return $this->render('register.php', ['error' => 'Phone number already exists']);
            }

            // Save user
            if ($user->save()) {
                // Registration successful
                session_start();
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_role'] = $user->role;
                $_SESSION['user_name'] = $user->firstName . ' ' . $user->lastName;
                // Add initial user stat entry
                $userStat = new UserStat();
                $userStat->add($user->id);
                // add them to session
                $_SESSION['vote_count'] = 0;
                $_SESSION['answer_count'] = 0;
                $_SESSION['ask_count'] = 0;
                $_SESSION['profile_view_count'] = 0;
                $_SESSION['last_viewed_user_id'] = -1;

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

    // API: Verify current password
    public function verifyCurrentPasswordAction(Request $request)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
            exit;
        }

        $hashedPassword = $request->get('hashed_password');
        if (empty($hashedPassword)) {
            echo json_encode(['success' => false, 'message' => 'Current password is required.']);
            exit;
        }

        $user = new User();
        $foundUser = $user->findById($_SESSION['user_id']);
        if (!$foundUser) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Double-hash: client sends SHA-256, server hashes again
        $serverHash = hash('sha256', $hashedPassword);
        if (hash_equals($serverHash, $foundUser->password_hash)) {
            echo json_encode(['success' => true, 'message' => 'Password verified.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        }
        exit;
    }

    // API: Change password (requires OTP verification)
    public function changePasswordAction(Request $request)
    {
        header('Content-Type: application/json');

        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
            exit;
        }

        if (empty($_SESSION['otp_verified']) || $_SESSION['otp_verified'] !== true) {
            echo json_encode(['success' => false, 'message' => 'OTP verification required.']);
            exit;
        }

        $newHashedPassword = $request->get('new_hashed_password');
        if (empty($newHashedPassword)) {
            echo json_encode(['success' => false, 'message' => 'New password is required.']);
            exit;
        }

        $user = new User();
        $foundUser = $user->findById($_SESSION['user_id']);
        if (!$foundUser) {
            echo json_encode(['success' => false, 'message' => 'User not found.']);
            exit;
        }

        // Double-hash: client sends SHA-256, server hashes again
        $foundUser->password_hash = hash('sha256', $newHashedPassword);
        $result = $foundUser->updatePassword();

        if ($result) {
            // Clear OTP session vars
            unset($_SESSION['otp_verified']);
            unset($_SESSION['otp_id']);
            echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
        }
        exit;
    }

    // API: Check if email or phone already exists
    public function checkExistsAction(Request $request)
    {
        header('Content-Type: application/json');

        $field = $request->get('field'); // 'email' or 'phone'
        $value = $request->get('value');

        if (!$field || !$value) {
            echo json_encode(['exists' => false, 'error' => 'Missing parameters']);
            exit;
        }

        $user = new User();

        if ($field === 'email') {
            $exists = $user->emailExists($value);
            echo json_encode([
                // 'exists' => $exists,
                'exists' => false, // temorarily disable existence check to avoid registration issues, will fix later
                'message' => $exists ? 'This email is already registered.' : ''
            ]);
        } elseif ($field === 'phone') {
            $exists = $user->phoneExists($value);
            echo json_encode([
                // 'exists' => $exists,
                'exists' => false, // temorarily disable existence check to avoid registration issues, will fix later
                'message' => $exists ? 'This phone number is already registered.' : ''
            ]);
        } else {
            echo json_encode(['exists' => false, 'error' => 'Invalid field']);
        }
        exit;
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
