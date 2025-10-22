<?php

namespace app\models;

use app\core\Database;

class Major
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Add a new major
    public function add($name)
    {
        try {
            $sql = "INSERT INTO majors (name) VALUES (:name)";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':name', $name);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Major add error: " . $e->getMessage());
            return false;
        }
    }

    // Remove a major by ID
    public function remove($id)
    {
        try {
            $sql = "DELETE FROM majors WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':id', $id);

            return $stmt->execute();
        } catch (\PDOException $e) {
            error_log("Major remove error: " . $e->getMessage());
            return false;
        }
    }

    // Get all majors as an array of objects
    public function getAll()
    {
        try {
            $sql = "SELECT * FROM majors";
            $result = $this->db->query($sql);

            $majors = [];
            while ($row = $result->fetch()) {
                $major = new \stdClass();
                $major->id = $row['id'];
                $major->name = $row['name'];
                $majors[] = $major;
            }

            return $majors;
        } catch (\PDOException $e) {
            error_log("Major getAll error: " . $e->getMessage());
            return [];
        }
    }
}