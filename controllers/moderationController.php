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
        try {
            $reports = $this->qaReportModel->getResolvedReports();
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
        try {
            $reports = $this->qaReportModel->getForwardedReports();
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
        $action = strtolower(trim((string) $request->get('action')));

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

    // for admin
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

}