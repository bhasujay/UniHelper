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

        $lookahead_default = 120;
        $lookahead_minutes = filter_var($request->get('lookahead_minutes'), FILTER_VALIDATE_INT, [
            'options' => [
                'min_range' => 1,
                'max_range' => 1440,
            ],
        ]);
        if ($lookahead_minutes === false) {
            $lookahead_minutes = $lookahead_default;
        }

        $log_file = dirname(__DIR__) . '/tools/artifacts/peer_session_mail_sent_ids.log';
        $sent_session_ids = $this->load_sent_session_ids($log_file);

        $sessions = $this->model->get_sessions_for_upcoming_window($lookahead_minutes);

        $logged_session_ids = [];
        $skipped_session_ids = [];
        $emails_sent = 0;

        foreach ($sessions as $session) {
            $session_id = (int) ($session['id'] ?? 0);
            if ($session_id <= 0) {
                continue;
            }

            if (isset($sent_session_ids[$session_id])) {
                $skipped_session_ids[] = $session_id;
                continue;
            }

            $subscribers = $this->model->get_subscribers_for_session($session_id);

            foreach ($subscribers as $subscriber) {
                $email = trim((string) ($subscriber['email'] ?? ''));
                if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    continue;
                }

                $mail = new mailer($email);
                $body = $this->build_reminder_body($session);
                $is_sent = $mail->sendEmail(
                    "Reminder: Your peer session '{$session['name']}' starts soon",
                    $body
                );

                if ($is_sent) {
                    $emails_sent++;
                }
            }

            $sent_session_ids[$session_id] = true;
            $logged_session_ids[] = $session_id;
        }

        $log_saved = $this->save_sent_session_ids($log_file, $sent_session_ids);

        echo json_encode([
            'success' => true,
            'message' => 'Peer session reminder job completed.',
            'lookahead_minutes' => $lookahead_minutes,
            'sessions_found' => count($sessions),
            'sessions_logged' => count($logged_session_ids),
            'emails_sent' => $emails_sent,
            'logged_session_ids' => $logged_session_ids,
            'skipped_session_ids' => $skipped_session_ids,
            'log_file' => $log_file,
            'log_saved' => $log_saved,
        ]);
    }

    private function load_sent_session_ids($log_file)
    {
        $sent_session_ids = [];
        if (!is_file($log_file)) {
            return $sent_session_ids;
        }

        $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return $sent_session_ids;
        }

        foreach ($lines as $line) {
            $id = (int) trim($line);
            if ($id > 0) {
                $sent_session_ids[$id] = true;
            }
        }

        return $sent_session_ids;
    }

    private function save_sent_session_ids($log_file, array $sent_session_ids)
    {
        $log_dir = dirname($log_file);
        if (!is_dir($log_dir) && !mkdir($log_dir, 0775, true) && !is_dir($log_dir)) {
            return false;
        }

        $id_list = array_keys($sent_session_ids);
        sort($id_list, SORT_NUMERIC);
        $payload = implode(PHP_EOL, $id_list) . PHP_EOL;

        return file_put_contents($log_file, $payload, LOCK_EX) !== false;
    }

    private function build_reminder_body(array $session)
    {
        $session_name = htmlspecialchars((string) ($session['name'] ?? 'your session'), ENT_QUOTES, 'UTF-8');
        $scheduled_at_raw = (string) ($session['scheduled_at'] ?? '');
        $scheduled_at = strtotime($scheduled_at_raw);
        $schedule_text = $scheduled_at !== false
            ? date('Y-m-d H:i', $scheduled_at)
            : 'soon';

        return "Dear user,<br><br>" .
            "This is a reminder that your peer session '{$session_name}' starts at {$schedule_text}.<br><br>" .
            "Best regards,<br>UniHelper Team";
    }
}