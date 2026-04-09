<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/core/mailer.php';
require_once dirname(__DIR__, 1) . '/models/otp.php';

use app\core\Request;
use app\core\mailer;
use app\models\otpModel;

// Controller for OTP-related actions
class OtpController
{
    private $otpModel;
    private $otp;

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
    // Set JSON content type
    header('Content-Type: application/json');

    try {
        // Generate OTP
        $otp = $this->generateOTP();
        $otpHash = $this->hashOtp($otp);
        $expiresAt = time() + 300;

        // Send OTP via email
        $email = $request->get('email');
        $mailer = new mailer($email);
        $mailer->sendOTP($otp);

        // Store OTP in DB
        $data = [
            'identifier' => $email ?? null,
            'otp_hash'   => $otpHash,
            'expires_at' => $expiresAt,
            'attempts'   => 0,
            'is_used'    => 0,
        ];
        $otpId = $this->otpModel->insert($data);

        $_SESSION['otp_id'] = $otpId;
        $_SESSION['otp_verified'] = false;

        error_log('Generated OTP: ' . $otp);

        // Send JSON response immediately
        echo json_encode([
            'success' => true,
            'message' => 'OTP sent successfully.'
        ]);
        exit;

    } catch (\Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate OTP.',
            'error' => $e->getMessage()
        ]);
        exit;
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
            exit;
        }

        $otpRecord = $this->otpModel->getOTP($otpId);

        if (!$otpRecord) {
            echo json_encode(['success' => false, 'message' => 'OTP not found.']);
            exit;
        }

        if ($otpRecord['is_used']) {
            echo json_encode(['success' => false, 'message' => 'OTP already used.']);
            exit;
        }

        if ($otpRecord['expires_at'] < time()) {
            echo json_encode(['success' => false, 'message' => 'OTP expired.']);
            exit;
        }

        // Check attempts (limit: 5)
        if ($otpRecord['attempts'] >= 5) {
            echo json_encode(['success' => false, 'message' => 'Too many attempts.']);
            exit;
        }

        // Verify OTP
        if (password_verify($userOtp, $otpRecord['otp_hash'])) {
            $this->otpModel->update($otpId, ['is_used' => 1]);
            $_SESSION['otp_verified'] = true;
            echo json_encode(['success' => true, 'message' => 'OTP verified.']);
            exit;
        } else {
            $this->otpModel->update($otpId, ['attempts' => $otpRecord['attempts'] + 1]);
            echo json_encode([
                'success' => false,
                'message' => 'Invalid OTP.',
            ]);
            exit;
        }
    }

}