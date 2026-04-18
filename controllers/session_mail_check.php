<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/session_mail_check.php';
require_once dirname(__DIR__, 1) . '/core/mailer.php';

use app\models\session_mail_check;
use app\core\Request;
use app\core\mailer;

class session_mail_checkController
{
    private $model;

    public function __construct()
    {
        $this->model = new session_mail_check();
    }

    // ---------------------------------------------------------------
    // GET ?controller=session_mail_checkController&action=send_mails_for_session&passkey=your_passkey_here
    // ---------------------------------------------------------------
    public function send_mails_for_session(Request $request)
    {
        header('Content-Type: application/json');

        // check the passkey, if the passkey is not correct, return 401
        $passkey = $request->get('passkey');
        if ($passkey !== "unihleper-qnvi24grgp18345y1ng4f1n34tu1f385gb") {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized: Invalid passkey.'
            ]);
            return;
        }
        
        // this is an array of id and name of the sessions that are starting in the next hour, we will use this to send emails to the subscribers of these sessions
        $sessions = $this->model->get_sessions_for_next_hour();
        foreach ($sessions as $session) {
            $subscribers = $this->model->get_subscribers_for_session($session['id']);
            foreach ($subscribers as $subscriber) {
                $mail = new mailer($subscriber['email']);
                $mail->sendEmail(
                    "Reminder: Your session '{$session['name']}' starts within an hour",
                    "Dear user,<br><br>This is a reminder that your session '{$session['name']}' is starting within an hour.<br><br>Best regards,<br>UniHelper Team"
                );
            }
        }
    }
}