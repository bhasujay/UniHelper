<?php

namespace app\models;

use app\core\Database;

class DegreeProgram {
    
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }
    
    // Method to add a degree
    public function addDegree($data) {
        $query = "INSERT INTO degree_program (name, university_id, stream, unicode, major_id) 
                  VALUES (:name, :university_id, :stream, :unicode, :major_id)";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':university_id', $data['university_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':stream', $data['stream']);
        $stmt->bindParam(':unicode', $data['unicode']);
        $stmt->bindParam(':major_id', $data['major_id'], \PDO::PARAM_INT);
        
        if($stmt->execute()) {
            return $this->db->lastInsertId();
        }
        return false;
    }
    
    // Method to delete a degree
    public function deleteDegree($id) {
        $query = "DELETE FROM degree_program WHERE program_id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Method to update a degree
    public function updateDegree($id, $data) {
        $query = "UPDATE degree_program 
                  SET name = :name, university_id = :university_id, stream = :stream, 
                      unicode = :unicode, major_id = :major_id
                  WHERE program_id = :id";
                  
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':university_id', $data['university_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':stream', $data['stream']);
        $stmt->bindParam(':unicode', $data['unicode']);
        $stmt->bindParam(':major_id', $data['major_id'], \PDO::PARAM_INT);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Method to get a degree by id
    public function getDegreeById($id) {
        $query = "SELECT * FROM degree_program WHERE program_id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
    
    // Method to get all degrees
    public function getAllDegrees() {
        // Join with universities table to get university name
        $query = "SELECT d.program_id as id, d.name, d.stream, d.unicode, 
                  u.name as university, m.name as major
                  FROM degree_program d
                  LEFT JOIN universities u ON d.university_id = u.id
                  LEFT JOIN majors m ON d.major_id = m.id";
        
        $result = $this->db->query($query);
        $degrees = [];
        
        while($row = $result->fetch()) {
            // Convert array to object
            $degree = new \stdClass();
            $degree->id = $row['id'];
            $degree->name = $row['name'];
            $degree->stream = $row['stream'];
            $degree->unicode = $row['unicode'];
            $degree->description = ''; // Column doesn't exist in table
            $degree->duration = ''; // Column doesn't exist in table
            $degree->university = $row['university'];
            $degree->major = $row['major'];
            $degree->status = 'Active'; // Default status since it's not in your table
            
            $degrees[] = $degree;
        }
        
        return $degrees;
    }
}
?>