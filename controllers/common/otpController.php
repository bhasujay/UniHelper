<?php

namespace app\controllers;

use app\models\otpModel;

// Controller for OTP-related actions
class OtpController
{
    private $otpModel;

    public function __construct()
    {
        $this->otpModel = new otpModel();
    }

    private function hashOtp($otp)
    {
        return password_hash($otp, PASSWORD_BCRYPT);
    }

    private function generateOTP()
    {
        $this->otp = rand(100000, 999999); // Generate a 6-digit OTP
        // Store the OTP in the database or session as needed
        
        return $this->otp;
    }

    // Action to generate OTP
    public function generateOtpAction()
    {
        $otp = $this->generateOTP();
        $otpHash = $this->hashOtp($otp);
        $expiresAt = time() + 300;

        $data = [
            'user_id'   => $_SESSION['user_id'],
            'otp_hash'  => $otpHash,
            'expires_at'=> $expiresAt,
            'attempts'  => 0,
            'is_used'   => 0,
        ];

        $otpId = $this->otpModel->insert($data);

        $_SESSION['otp_id'] = $otpId;
    }

    // Action to validate OTP
    public function validateOtpAction()
    {
        header('Content-Type: application/json');

        // Get POST data
        $userOtp = $_POST['otp'] ?? null;
        $otpId = $_SESSION['otp_id'] ?? null;

        // set session otp_verified to false initially
        $_SESSION['otp_verified'] = false;

        if (!$userOtp || !$otpId) {
            echo json_encode(['success' => false, 'message' => 'Missing OTP or session.']);
            return;
        }

        $otpRecord = $this->otpModel->getOTP($otpId);

        if (!$otpRecord) {
            echo json_encode(['success' => false, 'message' => 'OTP not found.']);
            return;
        }

        if ($otpRecord['is_used']) {
            echo json_encode(['success' => false, 'message' => 'OTP already used.']);
            return;
        }

        if ($otpRecord['expires_at'] < time()) {
            echo json_encode(['success' => false, 'message' => 'OTP expired.']);
            return;
        }

        // Check attempts (optional: limit attempts, e.g., 5)
        if ($otpRecord['attempts'] >= 5) {
            echo json_encode(['success' => false, 'message' => 'Too many attempts.']);
            return;
        }

        // Verify OTP
        if (password_verify($userOtp, $otpRecord['otp_hash'])) {
            // Mark OTP as used
            $sql = "UPDATE user_otps SET is_used = 1 WHERE id = :id";
            $this->otpModel->db->prepare($sql)->execute([':id' => $otpId]);
            $_SESSION['otp_verified'] = true;
            echo json_encode(['success' => true, 'message' => 'OTP verified.']);
        } else {
            // Increment attempts
            $sql = "UPDATE user_otps SET attempts = attempts + 1 WHERE id = :id";
            $this->otpModel->db->prepare($sql)->execute([':id' => $otpId]);
            echo json_encode(['success' => false, 'message' => 'Invalid OTP.']);
        }
    }


}