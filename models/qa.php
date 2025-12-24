<?php

namespace app\models;

use app\core\Database;


class Qna 
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Insert a new QnA record
    public function insert($data)
    {

    }

    // Delete a QnA by its ID
    public function delete($id)
    {

    }

    // Get QnA record by ID
    public function getQna($id)
    {

    }

    // Update QnA record
    public function update($id, $data)
    {

    }
}
