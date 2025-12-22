<?php

namespace app\controllers;

require_once dirname(__DIR__, 2) . '/core/mailer.php';
require_once dirname(__DIR__, 2) . '/models/otp.php';

use app\core\Request;
use app\core\mailer;
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
    public function generateOtpAction(Request $request)
    {
        header('Content-Type: application/json');

        try {
            $otp = $this->generateOTP();
            $otpHash = $this->hashOtp($otp);
            $expiresAt = time() + 300;

            // Send OTP via email
            $email = $request->get('email');
            $mailer = new mailer($email);
            $mailer->sendOTP($otp);

            // Store OTP details in the database
            $data = [
                'identifier'   => $email ?? null,
                'otp_hash'  => $otpHash,
                'expires_at'=> $expiresAt,
                'attempts'  => 0,
                'is_used'   => 0,
            ];
            $otpId = $this->otpModel->insert($data);

            $_SESSION['otp_id'] = $otpId;
            $_SESSION['otp_verified'] = false;

            echo json_encode(['success' => true, 'message' => 'OTP sent successfully.']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Failed to generate OTP.']);
        }
    }

    // Action to validate OTP
    public function validateOtpAction(Request $request)
    {
        header('Content-Type: application/json');

        // Get POST data
        $userOtp = $request->get('otp') ?? null;
        $otpId = $_SESSION['otp_id'] ?? null;

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
            // Mark OTP as used using the model's update method
            $this->otpModel->update($otpId, ['is_used' => 1]);

            $_SESSION['otp_verified'] = true;
            echo json_encode(['success' => true, 'message' => 'OTP verified.']);
        } else {
            // Increment attempts using the model's update method
            $this->otpModel->update($otpId, ['attempts' => $otpRecord['attempts'] + 1]);
            
            echo json_encode([
                'success' => false,
                'message' => 'Invalid OTP.',
                'user_otp' => $userOtp
            ]);
        }
    }

}