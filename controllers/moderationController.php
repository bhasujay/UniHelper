<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/moderation.php';

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

    // admin only
    public function getAllResolvedReports(Request $request)
    {
        // only for admin to view all resolved reports, this is useful for admins to monitor the moderation activities and ensure that moderators are taking appropriate actions on reported content, and to identify any patterns or trends in the types of content being reported and how they are being handled
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can view all resolved reports.',
            ], 403);
            return;
        }

        try {
            $reports = $this->qaReportModel->getAllResolvedReports();
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

    // admin only
    public function getAllForwardedReports(Request $request)
    {
        // only for admin to view all forwarded reports, this is useful for admins to monitor the moderation activities and ensure that moderators are taking appropriate actions on reported content, and to identify any patterns or trends in the types of content being reported and how they are being handled
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can view all forwarded reports.',
            ], 403);
            return;
        }

        try {
            $reports = $this->qaReportModel->getAllForwardedReports();
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

    // admin only
    public function deleteReport(Request $request)
    {
        $reportId = $request->get('report_id');

        // only admin can delete a report, this is useful when a report is deemed invalid or abusive, and we want to remove it from the system entirely, without taking any action on the reported content
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can delete reports.',
            ], 403);
            return;
        }

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

    public function unflagReport(Request $request)
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
            $revoked = $this->qaReportModel->unflag((int) $reportId);
            $this->json([
                'success' => $revoked,
                'message' => $revoked ? 'Report status reset to pending successfully.' : 'Report not found.',
            ], $revoked ? 200 : 404);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to reset report status.',
            ], 500);
        }
    }

    public function backToPending(Request $request)
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
            $revoked = $this->qaReportModel->backToPending((int) $reportId);
            $this->json([
                'success' => $revoked,
                'message' => $revoked ? 'Report status reset to pending successfully.' : 'Report not found.',
            ], $revoked ? 200 : 404);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to reset report status.',
            ], 500);
        }
    }

    // for admin To remove the content by the admin
    public function removeContent(Request $request)
    {
        $reportId = $request->get('report_id');

        // only admin can remove content, this is useful when a report is deemed valid and the content is found to be in violation of the platform's guidelines, and we want to remove the offending content from the system entirely, along with all associated reports
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can remove content.',
            ], 403);
            return;
        }

        if (empty($reportId) || !is_numeric($reportId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid report_id is required.',
            ], 400);
            return;
        }

        try {
            $removed = $this->qaReportModel->removeContent((int) $reportId, $request->session('user_id'));
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

    // Check the application status for the moderator whether it is accepted or rejected or still pending
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
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can review moderator applications.',
            ], 403);
            return;
        }

        $applicationId = $request->get('application_id');
        $action = strtolower(trim((string) $request->get('review_action')));

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

    public function getModeratorRequests(Request $request)
    {
        // only for admin to view moderator applications, this is useful for admins to review and manage moderator applications, and to identify potential candidates for the moderator role based on their motivations and qualifications
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can view moderator applications.',
            ], 403);
            return;
        }

        try {
            $applications = $this->qaReportModel->getModeratorRequests();
            $this->json([
                'success' => true,
                'data' => empty($applications) ? null : $applications,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch moderator applications.',
            ], 500);
        }
    }

    public function getCurrentModerators(Request $request)
    {
        // only for admin to view current moderators, this is useful for admins to monitor the current moderators on the platform, and to identify any potential issues or concerns with their performance or behavior, and to ensure that they are fulfilling their responsibilities effectively
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can view current moderators.',
            ], 403);
            return;
        }

        try {
            $moderators = $this->qaReportModel->getCurrentModerators();
            $this->json([
                'success' => true,
                'data' => empty($moderators) ? null : $moderators,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to fetch current moderators.',
            ], 500);
        }
    }

    public function removeModerator(Request $request)
    {
        $isAdmin = $request->session('user_role') === 'role-admin';
        if (!$isAdmin) {
            $this->json([
                'success' => false,
                'message' => 'Unauthorized. Only admins can remove moderators.',
            ], 403);
            return;
        }

        $userId = $request->get('user_id');
        if (empty($userId) || !is_numeric($userId)) {
            $this->json([
                'success' => false,
                'message' => 'Valid user_id is required.',
            ], 400);
            return;
        }

        try {
            $removed = $this->qaReportModel->removeModerator((int) $userId);
            $this->json([
                'success' => $removed,
                'message' => $removed ? 'Moderator access removed successfully.' : 'User not found or not a moderator.',
            ], $removed ? 200 : 404);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to remove moderator access.',
            ], 500);
        }
    }
}