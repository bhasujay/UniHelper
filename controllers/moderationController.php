<?php

namespace app\controllers;

require_once dirname(__DIR__) . '\models\qa-report.php';

use app\models\QaReport;
use app\core\Request;

class ModerationController
{
    private $qaReportModel;
    private const VALID_ACTIONS = ['ignored', 'flagged', 'forwarded to admin'];

    public function __construct()
    {
        $this->qaReportModel = new QaReport();
    }

    private function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    public function getPendingReports(Request $request)
    {
        try {
            $reports = $this->qaReportModel->getPendingReports();
            $this->json([
                'success' => true,
                'data' => empty($reports) ? null : $reports,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch pending reports.',
            ], 500);
        }
    }

    public function getResolvedReports(Request $request)
    {
        $moderatorId = $request->session('user_id');
        if (empty($moderatorId) || !is_numeric($moderatorId)) {
            $this->json([
                'success' => false,
                'message' => 'User must be logged in as moderator to view resolved reports.',
            ], 401);
            return;
        }

        try {
            $reports = $this->qaReportModel->getResolvedReports((int) $moderatorId);
            $this->json([
                'success' => true,
                'data' => empty($reports) ? null : $reports,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch resolved reports.',
            ], 500);
        }
    }

    public function getForwardedReports(Request $request)
    {
        $moderatorId = $request->session('user_id');
        if (empty($moderatorId) || !is_numeric($moderatorId)) {
            $this->json([
                'success' => false,
                'message' => 'User must be logged in as moderator to view forwarded reports.',
            ], 401);
            return;
        }

        try {
            $reports = $this->qaReportModel->getForwardedReports((int) $moderatorId);
            $this->json([
                'success' => true,
                'data' => empty($reports) ? null : $reports,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch forwarded reports.',
            ], 500);
        }
    }

    public function takeAction(Request $request)
    {
        $reportId = $request->get('report_id');
        $rawAction = $request->get('report_action');
        
        $action = strtolower(trim((string) $rawAction));

        if (empty($reportId) || !is_numeric($reportId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid report_id is required.',
            ], 400);
            return;
        }

        if (!in_array($action, self::VALID_ACTIONS, true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid action. Allowed actions: ignored, flagged, forwarded to admin.',
            ], 400);
            return;
        }

        $moderatorId = $request->session('user_id');
        if (empty($moderatorId) || !is_numeric($moderatorId)) {
            $moderatorId = null;
        } else {
            $moderatorId = (int) $moderatorId;
        }

        if ($action === 'forwarded to admin' && $moderatorId === null) {
            $this->json([
                'success' => false,
                'message' => 'User must be logged in as moderator to forward reports to admin.',
            ], 401);
            return;
        }

        try {
            $updated = $this->qaReportModel->takeAction((int) $reportId, $action, $moderatorId);

            $this->json([
                'success' => $updated,
                'message' => $updated ? 'Action applied successfully.' : 'Report not found.',
            ], $updated ? 200 : 404);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to apply action on report.',
            ], 500);
        }

    }

    public function deleteReport(Request $request)
    {
        $reportId = $request->get('report_id');

        if (empty($reportId) || !is_numeric($reportId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid report_id is required.',
            ], 400);
            return;
        }

        try {
            $deleted = $this->qaReportModel->deleteReport((int) $reportId);
            $this->json([
                'success' => $deleted,
                'message' => $deleted ? 'Report deleted successfully.' : 'Report not found.',
            ], $deleted ? 200 : 404);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to delete report.',
            ], 500);
        }
    }

    // for admin To remove the content
    public function removeContent(Request $request)
    {
        $reportId = $request->get('report_id');

        if (empty($reportId) || !is_numeric($reportId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid report_id is required.',
            ], 400);
            return;
        }

        try {
            $removed = $this->qaReportModel->removeContent((int) $reportId);
            $this->json([
                'success' => $removed,
                'message' => $removed
                    ? 'Content removed and associated reports deleted successfully.'
                    : 'Report not found.',
            ], $removed ? 200 : 404);
        } catch (\InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to remove content.',
            ], 500);
        }
    }

    // moderator application
    public function applyForModerator(Request $request)
    {
        $userId = $request->session('user_id');
        $motivation = trim((string) $request->get('motivation'));

        if (empty($userId) || !is_numeric($userId)) {
            $this->json([
                'success' => false,
                'message' => 'User must be logged in to apply for moderator.',
            ], 401);
            return;
        }

        if (empty($motivation)) {
            $this->json([
                'success' => false,
                'message' => 'Motivation is required to apply for moderator.',
            ], 400);
            return;
        }

        try {
            $applied = $this->qaReportModel->applyForModerator((int) $userId, $motivation);
            $this->json([
                'success' => $applied,
                'message' => 'Moderator application submitted successfully.',
            ], $applied ? 200 : 409);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to submit moderator application.',
            ], 500);
        }
    }

    // Check the application status
    public function checkModeratorApplicationStatus(Request $request)
    {
        $userId = $request->session('user_id');

        if (empty($userId) || !is_numeric($userId)) {
            $this->json([
                'success' => false,
                'message' => 'User must be logged in to check application status.',
            ], 401);
            return;
        }

        try {
            $status = $this->qaReportModel->checkModeratorApplicationStatus((int) $userId);
            $this->json([
                'success' => true,
                'data' => ['status' => $status],
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to check moderator application status.',
            ], 500);
        }
    }

    // This is for the admin either Accept or reject the application 
    public function reviewModeratorApplication(Request $request)
    {
        $applicationId = $request->get('application_id');
        $action = strtolower(trim((string) $request->get('action')));

        if (empty($applicationId) || !is_numeric($applicationId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid application_id is required.',
            ], 400);
            return;
        }

        if (!in_array($action, ['accept', 'reject'], true)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid action. Allowed actions: accept, reject.',
            ], 400);
            return;
        }

        try {
            $reviewed = $this->qaReportModel->reviewModeratorApplication((int) $applicationId, $action);
            $this->json([
                'success' => $reviewed,
                'message' => $reviewed
                    ? "Application has been {$action}ed successfully."
                    : 'Application not found.',
            ], $reviewed ? 200 : 404);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => "Failed to {$action} moderator application.",
            ], 500);
        }
    }

}