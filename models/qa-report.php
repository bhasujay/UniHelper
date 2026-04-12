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

    private function getReportsByStatus(string $status): array
    {
        $sql = "
            SELECT
                r.report_id,
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
            ORDER BY r.created_at DESC, r.report_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['status' => $status]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getPendingReports()
    {
        return $this->getReportsByStatus('pending');
    }

    public function getResolvedReports()
    {
        return $this->getReportsByStatus('resolved');
    }

    public function getForwardedReports()
    {
        return $this->getReportsByStatus('forwarded_to_admin');
    }

    private function updateStatus($reportId, $status, $moderatorId = null)
    {
        $sql = "UPDATE reports SET status = :status, mod_id = :mod_id WHERE report_id = :report_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'status' => $status,
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

            $this->updateStatus((int) $reportId, $reportStatus, $moderatorId);
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
