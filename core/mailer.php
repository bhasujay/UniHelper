<?php

namespace app\core;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Load .env using vlucas/phpdotenv
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

class mailer
{
    protected $recipientEmail;
    protected $mail;

    public function __construct($recipientEmail)
    {
        $this->recipientEmail = $recipientEmail;
        $this->mail = new PHPMailer(true);
    }

    public function sendEmail($subject, $body)
    {
        $mail = $this->mail;
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['GMAIL_USERNAME'] ?? '';
            $mail->Password   = $_ENV['GMAIL_PASSWORD'] ?? '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;

            $mail->setFrom($_ENV['GMAIL_USERNAME'] ?? '', 'UniHelper');
            $mail->addAddress($this->recipientEmail);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }

    public function sendOTP($otp)
    {
        $subject = "Your One-Time Password (OTP) Code";
        $body = '
            <div style="font-family: Arial, sans-serif; max-width: 480px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 8px; padding: 32px 24px; background: #fafbfc;">
            <h2 style="color: #2d3748; margin-bottom: 16px;">UniHelper Verification</h2>
            <p style="font-size: 16px; color: #4a5568; margin-bottom: 24px;">
                Please use the following One-Time Password (OTP) to complete your verification process:
            </p>
            <div style="font-size: 32px; font-weight: bold; color: #3182ce; letter-spacing: 4px; margin-bottom: 24px; text-align: center;">
                ' . htmlspecialchars($otp) . '
            </div>
            <p style="font-size: 14px; color: #718096;">
                This OTP is valid for a limited time. If you did not request this, please ignore this email.
            </p>
            <hr style="margin: 32px 0 16px 0; border: none; border-top: 1px solid #e2e8f0;">
            <p style="font-size: 12px; color: #a0aec0; text-align: center;">
                &copy; ' . date('Y') . ' UniHelper. All rights reserved.
            </p>
            </div>
        ';
        return $this->sendEmail($subject, $body);
    }    
}
?>