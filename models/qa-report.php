<?php

namespace app\models;

use app\core\Database;

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

        $status = $action === 'accept' ? 'accepted' : 'rejected';

        $sql = "UPDATE moderator_requests SET status = :status WHERE request_id = :request_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $status,
            'request_id' => (int) $applicationId,
        ]);

        return $stmt->rowCount() > 0;
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

    public function getResolvedReports(int $moderatorId)
    {
        return $this->getReportsByStatus('resolved', $moderatorId);
    }

    public function getForwardedReports(int $moderatorId)
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

        $validActions = ['ignored', 'flagged', 'forwarded to admin'];
        if (!in_array($action, $validActions, true)) {
            throw new \InvalidArgumentException('Invalid action. Allowed actions: ignored, flagged, forwarded to admin.');
        }

        if ($action === 'forwarded to admin' && $moderatorId === null) {
            throw new \InvalidArgumentException('A valid moderator ID is required to forward reports to admin.');
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
                } else {
                    $flagStmt = $this->db->prepare("UPDATE answers SET status = 'flagged' WHERE a_id = :id");
                    $flagStmt->execute(['id' => (int) $report['a_id']]);
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

    public function removeContent($reportId)
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

                // Mark all reports about this question as resolved and clear mod_id
                $reportsStmt = $this->db->prepare("UPDATE reports SET status = 'resolved', mod_id = NULL WHERE q_id = :id");
                $reportsStmt->execute(['id' => $contentId]);
            } else {
                $contentId = (int) $report['a_id'];

                // Mark content as removed (schema-dependent value)
                $contentStmt = $this->db->prepare("UPDATE answers SET status = 'removed' WHERE a_id = :id");
                $contentStmt->execute(['id' => $contentId]);

                // Mark all reports about this answer as resolved and clear mod_id
                $reportsStmt = $this->db->prepare("UPDATE reports SET status = 'resolved', mod_id = NULL WHERE a_id = :id");
                $reportsStmt->execute(['id' => $contentId]);
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
}
