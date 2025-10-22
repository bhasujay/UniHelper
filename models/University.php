<?php

namespace app\models;

use app\core\Database;

class University
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Add a new university
    public function add($name)
    {
        try {
            $sql = "INSERT INTO universities (name) VALUES (:name)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("University add error: " . $e->getMessage());
            return false;
        }
    }

    // Remove a university by ID
    public function remove($id)
    {
        try {
            $sql = "DELETE FROM universities WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("University remove error: " . $e->getMessage());
            return false;
        }
    }

    // Get all universities as an array of objects
    public function getAll()
    {
        try {
            $sql = "SELECT * FROM universities";
            $result = $this->db->query($sql);

            $universities = [];
            while ($row = $result->fetch()) {
                $university = new \stdClass();
                $university->id = $row['id'];
                $university->name = $row['name'];
                $universities[] = $university;
            }

            return $universities;
        } catch (\PDOException $e) {
            error_log("University getAll error: " . $e->getMessage());
            return [];
        }
    }
}