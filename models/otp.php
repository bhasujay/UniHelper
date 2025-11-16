<?php

namespace app\models;

use app\core\Database;

class otp
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    // Insert a new OTP record
    public function insert($data)
    {
        $sql = "INSERT INTO user_otps (user_id, otp_hash, expires_at, attempts, is_used)
                VALUES (:user_id, :otp_hash, :expires_at, :attempts, :is_used)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'   => $data['user_id'],
            ':otp_hash'  => $data['otp_hash'],
            ':expires_at'=> $data['expires_at'],
            ':attempts'  => $data['attempts'] ?? 0,
            ':is_used'   => $data['is_used'] ?? 0,
        ]);
        return $this->db->lastInsertId();
    }

    // Delete an OTP by its ID
    public function delete($id)
    {
        $sql = "DELETE FROM user_otps WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }

    // Get OTP record by ID
    public function getOTP($id)
    {
        $sql = "SELECT * FROM user_otps WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Delete all expired OTPs
    public function deleteExpiredOTPs()
    {
        $now = time();
        $sql = "DELETE FROM user_otps WHERE expires_at < :now OR is_used = 1";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':now' => $now]);
    }
}