<?php

namespace app\models;

use app\core\Database;

class User
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Properties
    public $id;
    public $firstName;
    public $lastName;
    public $email;
    public $phone;
    public $password_hash;
    public $role;
    public $alYear;
    public $University;
    public $major;
    public $profileRole;
    public $profilePicture;
    public $createdAt;

    // Save new user to database
    public function save()
    {
        try {
            $sql = "INSERT INTO users (first_name, last_name, email, phone, password_hash, role, al_year, university, major, profile_role, profile_picture, created_at) 
                    VALUES (:firstName, :lastName, :email, :phone, :password_hash, :role, :alYear, :University, :major, :profileRole, :profilePicture, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':firstName', $this->firstName);
            $stmt->bindParam(':lastName', $this->lastName);
            $stmt->bindParam(':email', $this->email);
            $stmt->bindParam(':phone', $this->phone);
            $stmt->bindParam(':password_hash', $this->password_hash);
            $stmt->bindParam(':role', $this->role);
            $stmt->bindParam(':alYear', $this->alYear);
            $stmt->bindParam(':University', $this->University);
            $stmt->bindParam(':major', $this->major);
            $stmt->bindParam(':profileRole', $this->profileRole);
            $stmt->bindParam(':profilePicture', $this->profilePicture);
            
            $result = $stmt->execute();
            
            if ($result) {
                $this->id = $this->db->lastInsertId();
                return true;
            } else {
                return false;
            }
        } catch (\PDOException $e) {
            error_log("User save error: " . $e->getMessage());
            return ['error' => 'Database error: ' . $e->getMessage()];
        }
    }

    // Find user by email
    public function findByEmail($email)
    {
        try {
            $sql = "SELECT * FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $userData = $stmt->fetch();
            
            if ($userData) {
                $user = new User();
                $user->id = $userData['id'];
                $user->firstName = $userData['first_name'];
                $user->lastName = $userData['last_name'];
                $user->email = $userData['email'];
                $user->password_hash = $userData['password_hash'];
                $user->role = $userData['role'];
                $user->phone = $userData['phone'];
                $user->profilePicture = $userData['profile_picture'];
                $user->alYear = $userData['al_year'];
                $user->University = $userData['university'];
                $user->major = $userData['major'];
                $user->profileRole = $userData['profile_role'];
                $user->createdAt = $userData['created_at'];
                
                return $user;
            }
            return null;
        } catch (\PDOException $e) {
            error_log("User findByEmail error: " . $e->getMessage());
            return null;
        }
    }

    // Check if email already exists
    public function emailExists($email)
    {
        try {
            $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            return $stmt->fetchColumn() > 0;
        } catch (\PDOException $e) {
            error_log("User emailExists error: " . $e->getMessage());
            return false;
        }
    }

    // Validate user data
    public function validate()
    {
        $errors = [];

        if (empty($this->firstName)) {
            $errors[] = "First name is required";
        }

        if (empty($this->lastName)) {
            $errors[] = "Last name is required";
        }

        if (empty($this->email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($this->email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($this->password_hash)) {
            $errors[] = "Password is required";
        }

        if (empty($this->phone)) {
            $errors[] = "Phone number is required";
        }

        if (empty($this->role)) {
            $errors[] = "Role is required";
        }

        // Add a line break to each error message
        foreach ($errors as &$error) {
            $error .= "<br>";
        }

        return $errors;
    }

    // Hash password
    public function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    // Verify password
    public function verifyPassword($password, $hash)
    {
        return password_verify($password, $hash);
    }

    // Get user by ID
    public function findById($id)
    {
        try {
            $sql = "SELECT * FROM users WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $userData = $stmt->fetch();
            
            if ($userData) {
                $user = new User();
                $user->id = $userData['id'];
                $user->firstName = $userData['first_name'];
                $user->lastName = $userData['last_name'];
                $user->email = $userData['email'];
                $user->password_hash = $userData['password_hash'];
                $user->role = $userData['role'];
                $user->phone = $userData['phone'];
                $user->profilePicture = $userData['profile_picture'];
                $user->alYear = $userData['al_year'];
                $user->University = $userData['university'];
                $user->profileRole = $userData['profile_role'];
                $user->major = $userData['major'];
                $user->createdAt = $userData['created_at'];
                
                return $user;
            }
            return null;
        } catch (\PDOException $e) {
            error_log("User findById error: " . $e->getMessage());
            return null;
        }
    }
}
