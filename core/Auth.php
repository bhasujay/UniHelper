<?php

namespace app\core;

/**
 * Authentication helper class
 * Provides utilities for checking authentication status and user roles
 */
class Auth
{
    /**
     * Check if user is currently logged in
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Get the current user's ID
     * @return int|null
     */
    public static function user()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get the current user's role
     * @return string|null
     */
    public static function role()
    {
        return $_SESSION['user_role'] ?? null;
    }

    /**
     * Get the current user's email
     * @return string|null
     */
    public static function email()
    {
        return $_SESSION['user_email'] ?? null;
    }

    /**
     * Get the current user's full name
     * @return string|null
     */
    public static function name()
    {
        return $_SESSION['user_name'] ?? null;
    }

    /**
     * Check if user has a specific role
     * @param string $role
     * @return bool
     */
    public static function hasRole($role)
    {
        return self::role() === $role;
    }

    /**
     * Require user to be logged in
     * Redirects to login if not authenticated
     */
    public static function requireAuth()
    {
        if (!self::check()) {
            header('Location: /UniHelper/login');
            exit;
        }
    }

    /**
     * Require user to have a specific role
     * Redirects to login if not authenticated
     * Redirects to correct dashboard if wrong role
     * @param string $requiredRole
     */
    public static function requireRole($requiredRole)
    {
        // First check if user is logged in
        if (!self::check()) {
            header('Location: /login');
            exit;
        }

        // Then check if user has the correct role
        if (!self::hasRole($requiredRole)) {
            // Redirect to user's correct dashboard
            self::redirectToCorrectDashboard();
        }
    }

    /**
     * Redirect user to their correct dashboard based on their role
     */
    public static function redirectToCorrectDashboard()
    {
        $role = self::role();
        
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
                // Unknown role, redirect to login
                header('Location: /UniHelper/login');
        }
        exit;
    }

    /**
     * Check if user is an applicant
     * @return bool
     */
    public static function isApplicant()
    {
        return self::hasRole('role-applicant');
    }

    /**
     * Check if user is an undergraduate student
     * @return bool
     */
    public static function isUndergrad()
    {
        return self::hasRole('role-undergrad');
    }

    /**
     * Check if user is a profile/admin
     * @return bool
     */
    public static function isProfile()
    {
        return self::hasRole('role-profile');
    }

    /**
     * Logout the current user
     * Destroys session and redirects to home
     */
    public static function logout()
    {
        // Destroy all session data
        session_destroy();
        
        // Redirect to home page
        header('Location: /UniHelper/home');
        exit;
    }

    /**
     * Get user data from session
     * @return array
     */
    public static function userData()
    {
        return [
            'id' => self::user(),
            'email' => self::email(),
            'name' => self::name(),
            'role' => self::role()
        ];
    }
}
