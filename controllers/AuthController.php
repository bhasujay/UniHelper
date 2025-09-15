<?php

namespace app\controllers;

use app\core\Application;
use app\core\Request;
use app\models\User;

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
                return $this->render('login.html', ['error' => 'Email and password are required']);
            }

            // Find user by email
            $user = new User();
            $foundUser = $user->findByEmail($email);

            // Verify the password using the same method as registration
            $passwordVerified = $this->verifyPasswordFromJS($hashedPassword, $foundUser->password_hash);
            
            if ($foundUser && $passwordVerified) {
                // Login successful
                session_start();
                $_SESSION['user_id'] = $foundUser->id;
                $_SESSION['user_email'] = $foundUser->email;
                $_SESSION['user_role'] = $foundUser->role;
                $_SESSION['user_name'] = $foundUser->firstName . ' ' . $foundUser->lastName;

                // Redirect based on role
                $this->redirectToDashboard($foundUser->role);
                exit;
            } else {
                return $this->render('login.html', ['error' => 'Invalid email or password']);
            }
        }

        return $this->render('login.html');
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
            $user->role = $this->mapRole($request->get('userRole'));
            
            // Role-specific fields
            if ($user->role === 'role-applicant') {
                $user->alYear = $request->get('alYear');
            } elseif ($user->role === 'role-undergrad') {
                $user->undergradUniversity = $request->get('undergradUniversity');
                $user->major = $request->get('major');
            } elseif ($user->role === 'role-profile') {
                $user->profileUniversity = $request->get('profileUniversity');
                $user->profileRole = $request->get('role');
            }

            // Handle password (already hashed by JavaScript)
            $hashedPassword = $request->get('hashed_password');
            $user->password_hash = $this->hashPasswordFromJS($hashedPassword);

            // Validate data
            $errors = $user->validate();
            
            if (!empty($errors)) {
                return $this->render('register.html', ['errors' => $errors]);
            }

            // Check if email already exists
            if ($user->emailExists($user->email)) {
                return $this->render('register.html', ['error' => 'Email already exists']);
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
                $this->redirectToDashboard($user->role);
                exit;
            } else {
                return $this->render('register.html', ['error' => 'Registration failed. Please try again.']);
            }
        }

        return $this->render('register.html');
    }

    // Logout user
    public function logout()
    {
        session_start();
        session_destroy();
        header('Location: /UniHelper/home');
        exit;
    }

    // Helper method to map role from form to database
    private function mapRole($formRole)
    {
        // Return the form role directly since database expects 'role-applicant', 'role-undergrad', etc.
        return $formRole ?? 'role-applicant';
    }

    // Helper method to hash password from JavaScript hash
    private function hashPasswordFromJS($jsHash)
    {
        // Since JavaScript already hashed with SHA-256, we'll use that as a base
        // and add additional PHP hashing for extra security
        return password_hash($jsHash, PASSWORD_DEFAULT);
    }

    // Helper method to verify password from JavaScript hash
    private function verifyPasswordFromJS($jsHash, $storedHash)
    {
        // Since JavaScript already hashed with SHA-256, we need to verify against
        // the stored hash which was created with password_hash($jsHash, PASSWORD_DEFAULT)
        return password_verify($jsHash, $storedHash);
    }

    // Helper method to redirect to appropriate dashboard
    private function redirectToDashboard($role)
    {
        switch ($role) {
            case 'role-applicant':
                header('Location: /UniHelper/dashboard/applicant');
                break;
            case 'role-undergrad':
                header('Location: /UniHelper/dashboard/undergraduate');
                break;
            case 'role-profile':
                header('Location: /UniHelper/dashboard/profile');
                break;
            default:
                header('Location: /UniHelper/dashboard/applicant');
        }
    }

    protected function render($view, $data = [])
    {
        // Extract data array to variables
        extract($data);
        require Application::$ROOT_DIR . "/views/$view";
    }
}
