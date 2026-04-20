<?php

namespace app\models;

use app\core\Database;

require_once dirname(__DIR__) . '/models/notify.php';

class QaReport
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function applyForModerator($user_id, $motivation)
    {
        $userId = (int) $user_id;
        $motivation = trim((string) $motivation);

        // Check for existing request for this user
        $checkSql = "SELECT request_id, status FROM moderator_requests WHERE user_id = :user_id LIMIT 1";
        $checkStmt = $this->db->prepare($checkSql);
        $checkStmt->execute(['user_id' => $userId]);
        $existing = $checkStmt->fetch(\PDO::FETCH_ASSOC);

        if (!$existing) {
            // No existing request — insert new (status defaults to 'pending')
            $insertSql = "INSERT INTO moderator_requests (user_id, motivation) VALUES (:user_id, :motivation)";
            $insertStmt = $this->db->prepare($insertSql);
            $insertStmt->execute([
                'user_id' => $userId,
                'motivation' => $motivation,
            ]);

            return $insertStmt->rowCount() > 0;
        }

        $status = isset($existing['status']) ? $existing['status'] : null;

        // If already pending or accepted, do not allow re-apply
        if ($status === 'pending' || $status === 'accepted') {
            return false;
        }

        // Only reopen if previously rejected
        if ($status === 'rejected') {
            $updateSql = "UPDATE moderator_requests SET status = 'pending', motivation = :motivation, reviewed_at = NULL WHERE request_id = :request_id";
            $updateStmt = $this->db->prepare($updateSql);
            $updateStmt->execute([
                'motivation' => $motivation,
                'request_id' => (int) $existing['request_id'],
            ]);

            return $updateStmt->rowCount() > 0;
        }

        // For any other status, do not allow re-apply
        return false;
    }

    public function checkModeratorApplicationStatus($user_id)
    {
        // Check the application status
        // If there is no record of this then we should send 'clear' as status, otherwise return the actual status
        $sql = "SELECT status FROM moderator_requests WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => (int) $user_id]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result ? $result['status'] : 'clear';
    }

    public function reviewModeratorApplication($applicationId, $action)
    {
        $action = strtolower(trim((string) $action));
        if (!in_array($action, ['accept', 'reject'], true)) {
            throw new \InvalidArgumentException('Invalid action. Allowed actions: accept, reject.');
        }

        $connection = $this->db->getConnection();
        $connection->beginTransaction();

        try {
            $status = $action === 'accept' ? 'accepted' : 'rejected';

            $sql = "UPDATE moderator_requests SET status = :status WHERE request_id = :request_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'status' => $status,
                'request_id' => (int) $applicationId,
            ]);

            if ($stmt->rowCount() > 0) {
                $sel = "SELECT user_id FROM moderator_requests WHERE request_id = :request_id";
                $selStmt = $this->db->prepare($sel);
                $selStmt->execute(['request_id' => (int) $applicationId]);
                $req = $selStmt->fetch(\PDO::FETCH_ASSOC);

                if ($req) {
                    $modValue = ($action === 'accept') ? 1 : 0;
                    $updUser = "UPDATE users SET moderator = :mod_val WHERE id = :user_id";
                    $updStmt = $this->db->prepare($updUser);
                    $updStmt->execute([
                        'mod_val' => $modValue, 
                        'user_id' => (int) $req['user_id']
                    ]);
                }
            }

            $connection->commit();
            return true;
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function removeModerator($userId)
    {
        $connection = $this->db->getConnection();
        $connection->beginTransaction();

        try {
            $sqlUser = "UPDATE users SET moderator = 0 WHERE id = :user_id";
            $stmtUser = $this->db->prepare($sqlUser);
            $stmtUser->execute(['user_id' => (int) $userId]);

            $sqlReq = "UPDATE moderator_requests SET status = 'rejected' WHERE user_id = :user_id AND status = 'accepted'";
            $stmtReq = $this->db->prepare($sqlReq);
            $stmtReq->execute(['user_id' => (int) $userId]);

            $connection->commit();
            return $stmtUser->rowCount() > 0 || $stmtReq->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    private function getReportsByStatus(string $status, ?int $moderatorId = null): array
    {
        $sql = "
            SELECT
                r.report_id,
                r.reason,
                r.action_taken,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN r.q_id
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN a.q_id
                    ELSE NULL
                END AS q_id,
                r.a_id,
                r.reporter_id,
                CONCAT(u.first_name, ' ', u.last_name) AS reporter_name,
                u.profile_picture AS reporter_profile_picture,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN 'question'
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN 'answer'
                    ELSE 'unknown'
                END AS reported_content_type,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN q.question
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN a.text
                    ELSE NULL
                END AS text,
                r.created_at AS created_time
            FROM reports r
            LEFT JOIN users u ON u.id = r.reporter_id
            LEFT JOIN questions q ON q.q_id = r.q_id
            LEFT JOIN answers a ON a.a_id = r.a_id
            WHERE r.status = :status
        ";

        $params = ['status' => $status];
        if ($moderatorId !== null) {
            $sql .= "\n                AND r.mod_id = :moderator_id";
            $params['moderator_id'] = $moderatorId;
        }

        $sql .= "\n            ORDER BY r.created_at DESC, r.report_id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPendingReports()
    {
        return $this->getReportsByStatus('pending');
    }

    public function getResolvedReports(?int $moderatorId = null)
    {
        return $this->getReportsByStatus('resolved', $moderatorId);
    }

    public function getForwardedReports(?int $moderatorId = null)
    {
        return $this->getReportsByStatus('forwarded_to_admin', $moderatorId);
    }

    private function updateStatus($reportId, $status, $actionTaken, $moderatorId = null)
    {
        $sql = "UPDATE reports SET status = :status, action_taken = :action_taken, mod_id = :mod_id WHERE report_id = :report_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $status,
            'action_taken' => $actionTaken,
            'mod_id' => $moderatorId,
            'report_id' => (int) $reportId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function takeAction($reportId, $action, $moderatorId = null)
    {
        $action = strtolower(trim((string) $action));
        $moderatorId = $moderatorId !== null ? (int) $moderatorId : null;

        $validActions = ['ignored', 'flagged', 'forwarded to admin'];
        if (!in_array($action, $validActions, true)) {
            throw new \InvalidArgumentException('Invalid action. Allowed actions: ignored, flagged, forwarded to admin.');
        }

        if ($action === 'forwarded to admin' && $moderatorId === null) {
            throw new \InvalidArgumentException('A valid moderator ID is required to forward reports to admin.');
        }

        if ($moderatorId !== null) {
            $moderatorCheckSql = "SELECT moderator FROM users WHERE id = :user_id LIMIT 1";
            $moderatorCheckStmt = $this->db->prepare($moderatorCheckSql);
            $moderatorCheckStmt->execute(['user_id' => $moderatorId]);
            $isModerator = (int) $moderatorCheckStmt->fetchColumn() === 1;

            if (!$isModerator) {
                throw new \InvalidArgumentException('Invalid moderator ID. The selected user is not an active moderator.');
            }
        }

        $sql = "SELECT report_id, q_id, a_id FROM reports WHERE report_id = :report_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => (int) $reportId]);
        $report = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$report) {
            return false;
        }

        $isQuestionReport = !empty($report['q_id']) && empty($report['a_id']);
        $isAnswerReport = !empty($report['a_id']) && empty($report['q_id']);

        if (!$isQuestionReport && !$isAnswerReport) {
            throw new \InvalidArgumentException('Invalid report content reference.');
        }

        $contentNeedsFlag = ($action === 'flagged' || $action === 'forwarded to admin');
        $reportStatus = ($action === 'forwarded to admin') ? 'forwarded_to_admin' : 'resolved';

        $connection = $this->db->getConnection();
        $connection->beginTransaction();

        try {
            if ($contentNeedsFlag) {
                if ($isQuestionReport) {
                    $flagStmt = $this->db->prepare("UPDATE questions SET status = 'flagged' WHERE q_id = :id");
                    $flagStmt->execute(['id' => (int) $report['q_id']]);

                    if ($flagStmt->rowCount() > 0) {
                        $authorStmt = $this->db->prepare("SELECT user_id FROM questions WHERE q_id = :id LIMIT 1");
                        $authorStmt->execute(['id' => (int) $report['q_id']]);
                        $authorId = (int) $authorStmt->fetchColumn();

                        if ($authorId > 0) {
                            (new Notify())->insertNotification(
                                $authorId,
                                'Your question has been flagged by moderation.',
                                'qa',
                                '/unihelper/qa-forum?question=' . (int) $report['q_id']
                            );
                        }
                    }
                } else {
                    $flagStmt = $this->db->prepare("UPDATE answers SET status = 'flagged' WHERE a_id = :id");
                    $flagStmt->execute(['id' => (int) $report['a_id']]);

                    if ($flagStmt->rowCount() > 0) {
                        $authorStmt = $this->db->prepare("SELECT user_id, q_id FROM answers WHERE a_id = :id LIMIT 1");
                        $authorStmt->execute(['id' => (int) $report['a_id']]);
                        $author = $authorStmt->fetch(\PDO::FETCH_ASSOC);

                        if ($author && !empty($author['user_id'])) {
                            (new Notify())->insertNotification(
                                (int) $author['user_id'],
                                'Your answer has been flagged by moderation.',
                                'qa',
                                '/unihelper/qa-forum?question=' . (int) $author['q_id'] . '&answer=' . (int) $report['a_id']
                            );
                        }
                    }
                }
            }

            $this->updateStatus((int) $reportId, $reportStatus, $action, $moderatorId);
            $connection->commit();

            return true;
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function deleteReport($reportId)
    {
        $sql = "DELETE FROM reports WHERE report_id = :report_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => (int) $reportId]);

        return $stmt->rowCount() > 0;
    }

    public function unflag($reportId)
    {
        $sql = "SELECT report_id, q_id, a_id FROM reports WHERE report_id = :report_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => (int) $reportId]);
        $report = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$report) {
            return false;
        }

        $isQuestionReport = !empty($report['q_id']) && empty($report['a_id']);
        $isAnswerReport = !empty($report['a_id']) && empty($report['q_id']);

        if (!$isQuestionReport && !$isAnswerReport) {
            throw new \InvalidArgumentException('Invalid report content reference.');
        }

        if ($isQuestionReport) {
            $unflagStmt = $this->db->prepare("UPDATE questions SET status = 'normal' WHERE q_id = :id");
            $unflagStmt->execute(['id' => (int) $report['q_id']]);
        } else {
            $unflagStmt = $this->db->prepare("UPDATE answers SET status = 'normal' WHERE a_id = :id");
            $unflagStmt->execute(['id' => (int) $report['a_id']]);
        }

        // also reset the report status back to pending and clear the action taken and mod_id, this is useful when a moderator wants to re-evaluate a report after unflagging the content
        $sql = "UPDATE reports SET status = 'pending', action_taken = NULL, mod_id = NULL WHERE report_id = :report_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => (int) $reportId]);

        return $stmt->rowCount() > 0;
    }

    public function backToPending($reportId)
    {
        $sql = "SELECT report_id, q_id, a_id FROM reports WHERE report_id = :report_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => (int) $reportId]);
        $report = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$report) {
            return false;
        }

        $isQuestionReport = !empty($report['q_id']) && empty($report['a_id']);
        $isAnswerReport = !empty($report['a_id']) && empty($report['q_id']);

        if (!$isQuestionReport && !$isAnswerReport) {
            throw new \InvalidArgumentException('Invalid report content reference.');
        }

        $connection = $this->db->getConnection();
        $connection->beginTransaction();

        try {
            // Re-open moderation report and clear assignment/action data.
            $sql = "UPDATE reports SET status = 'pending', action_taken = NULL, mod_id = NULL WHERE report_id = :report_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['report_id' => (int) $reportId]);

            // Restore content visibility when it had been moderated to flagged/removed.
            if ($isQuestionReport) {
                $contentStmt = $this->db->prepare("UPDATE questions SET status = 'normal' WHERE q_id = :id AND status IN ('flagged', 'removed')");
                $contentStmt->execute(['id' => (int) $report['q_id']]);
            } else {
                $contentStmt = $this->db->prepare("UPDATE answers SET status = 'normal' WHERE a_id = :id AND status IN ('flagged', 'removed')");
                $contentStmt->execute(['id' => (int) $report['a_id']]);
            }

            $connection->commit();
            return $stmt->rowCount() > 0;
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function removeContent($reportId, $adminId = null)
    {
        $sql = "SELECT report_id, q_id, a_id FROM reports WHERE report_id = :report_id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['report_id' => (int) $reportId]);
        $report = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$report) {
            return false;
        }

        $isQuestionReport = !empty($report['q_id']) && empty($report['a_id']);
        $isAnswerReport = !empty($report['a_id']) && empty($report['q_id']);

        if (!$isQuestionReport && !$isAnswerReport) {
            throw new \InvalidArgumentException('Invalid report content reference.');
        }

        $connection = $this->db->getConnection();
        $connection->beginTransaction();

        try {
            if ($isQuestionReport) {
                $contentId = (int) $report['q_id'];

                // Mark content as removed (schema-dependent value)
                $contentStmt = $this->db->prepare("UPDATE questions SET status = 'removed' WHERE q_id = :id");
                $contentStmt->execute(['id' => $contentId]);

                if ($contentStmt->rowCount() > 0) {
                    $authorStmt = $this->db->prepare("SELECT user_id FROM questions WHERE q_id = :id LIMIT 1");
                    $authorStmt->execute(['id' => $contentId]);
                    $authorId = (int) $authorStmt->fetchColumn();

                    if ($authorId > 0) {
                        (new Notify())->insertNotification(
                            $authorId,
                            'Your question has been removed by moderation.',
                            'qa',
                            '/unihelper/qa-forum?question=' . $contentId
                        );
                    }
                }

                // Mark all reports about this question as resolved and update mod_id
                $reportsStmt = $this->db->prepare("UPDATE reports SET status = 'resolved', action_taken = 'removed', mod_id = :mod_id WHERE q_id = :id");
                $reportsStmt->execute(['id' => $contentId, 'mod_id' => $adminId]);
            } else {
                $contentId = (int) $report['a_id'];

                // Mark content as removed (schema-dependent value)
                $contentStmt = $this->db->prepare("UPDATE answers SET status = 'removed' WHERE a_id = :id");
                $contentStmt->execute(['id' => $contentId]);

                if ($contentStmt->rowCount() > 0) {
                    $authorStmt = $this->db->prepare("SELECT user_id, q_id FROM answers WHERE a_id = :id LIMIT 1");
                    $authorStmt->execute(['id' => $contentId]);
                    $author = $authorStmt->fetch(\PDO::FETCH_ASSOC);

                    if ($author && !empty($author['user_id'])) {
                        (new Notify())->insertNotification(
                            (int) $author['user_id'],
                            'Your answer has been removed by moderation.',
                            'qa',
                            '/unihelper/qa-forum?question=' . (int) $author['q_id'] . '&answer=' . $contentId
                        );
                    }
                }

                // Mark all reports about this answer as resolved and update mod_id
                $reportsStmt = $this->db->prepare("UPDATE reports SET status = 'resolved', action_taken = 'removed', mod_id = :mod_id WHERE a_id = :id");
                $reportsStmt->execute(['id' => $contentId, 'mod_id' => $adminId]);
            }

            $connection->commit();
            return true;
        } catch (\Throwable $e) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            throw $e;
        }
    }

    public function getModeratorRequests()
    {
        $sql = "
            SELECT
                mr.request_id,
                mr.user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                u.profile_picture AS user_profile_picture,
                uni.name AS university_name,
                mr.motivation,
                mr.status,
                mr.created_at,
                mr.reviewed_at
            FROM moderator_requests mr
            INNER JOIN users u ON u.id = mr.user_id
            LEFT JOIN universities uni ON uni.id = u.university
            ORDER BY mr.created_at DESC, mr.request_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // this function gets the current moderators in the system, this is useful for admin to see the current moderators and their details
    public function getCurrentModerators()
    {
        $sql = "
            SELECT
                u.id AS user_id,
                CONCAT(u.first_name, ' ', u.last_name) AS user_name,
                u.profile_picture AS user_profile_picture,
                uni.name AS university_name,
                mr.reviewed_at AS accepted_at,
                COUNT(r.report_id) AS resolved_reports_count
            FROM users u
            LEFT JOIN moderator_requests mr
                ON mr.user_id = u.id
                AND mr.status = 'accepted'
            LEFT JOIN universities uni
                ON uni.id = u.university
            LEFT JOIN reports r
                ON r.mod_id = u.id
                AND r.status = 'resolved'
            WHERE u.moderator = 1
            GROUP BY u.id, u.first_name, u.last_name, u.profile_picture, uni.name, mr.reviewed_at
            ORDER BY COALESCE(mr.reviewed_at, u.created_at) DESC, u.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllResolvedReports()
    {
        $sql = "
            SELECT
                r.report_id,
                r.reason,
                r.status,
                r.action_taken,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN r.q_id
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN a.q_id
                    ELSE NULL
                END AS q_id,
                r.a_id,
                r.reporter_id,
                CONCAT(reporter.first_name, ' ', reporter.last_name) AS reporter_name,
                reporter.profile_picture AS reporter_profile_picture,
                r.mod_id AS moderator_id,
                CONCAT(moderator.first_name, ' ', moderator.last_name) AS moderator_name,
                moderator.profile_picture AS moderator_profile_picture,
                moderator.role AS moderator_role,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN 'question'
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN 'answer'
                    ELSE 'unknown'
                END AS reported_content_type,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN q.question
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN a.text
                    ELSE NULL
                END AS text,
                r.created_at AS created_time
            FROM reports r
            LEFT JOIN users reporter ON reporter.id = r.reporter_id
            LEFT JOIN users moderator ON moderator.id = r.mod_id
            LEFT JOIN questions q ON q.q_id = r.q_id
            LEFT JOIN answers a ON a.a_id = r.a_id
            WHERE r.status = 'resolved'
            ORDER BY r.created_at DESC, r.report_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAllForwardedReports()
    {
        $sql = "
            SELECT
                r.report_id,
                r.reason,
                r.status,
                r.action_taken,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN r.q_id
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN a.q_id
                    ELSE NULL
                END AS q_id,
                r.a_id,
                r.reporter_id,
                CONCAT(reporter.first_name, ' ', reporter.last_name) AS reporter_name,
                reporter.profile_picture AS reporter_profile_picture,
                r.mod_id AS moderator_id,
                CONCAT(moderator.first_name, ' ', moderator.last_name) AS moderator_name,
                moderator.profile_picture AS moderator_profile_picture,
                moderator.role AS moderator_role,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN 'question'
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN 'answer'
                    ELSE 'unknown'
                END AS reported_content_type,
                CASE
                    WHEN r.q_id IS NOT NULL AND r.a_id IS NULL THEN q.question
                    WHEN r.a_id IS NOT NULL AND r.q_id IS NULL THEN a.text
                    ELSE NULL
                END AS text,
                r.created_at AS created_time
            FROM reports r
            LEFT JOIN users reporter ON reporter.id = r.reporter_id
            LEFT JOIN users moderator ON moderator.id = r.mod_id
            LEFT JOIN questions q ON q.q_id = r.q_id
            LEFT JOIN answers a ON a.a_id = r.a_id
            WHERE r.status = 'forwarded_to_admin'
            ORDER BY r.created_at DESC, r.report_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
}
