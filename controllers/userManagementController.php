<?php

namespace app\controllers;

require_once dirname(__DIR__) . '/models/userManagement.php';

use app\core\Request;
use app\models\UserManagement;
use InvalidArgumentException;

class UserManagementController
{
	private $userManagementModel;

	public function __construct()
	{
		$this->userManagementModel = new UserManagement();
	}

	private function json(array $data, int $status = 200): void
	{
		http_response_code($status);
		header('Content-Type: application/json');
		echo json_encode($data);
	}

	private function ensureAdmin(Request $request): bool
	{
		if ($request->session('user_role') !== 'role-admin') {
			$this->json([
				'success' => false,
				'message' => 'Unauthorized. Only admins can access user management APIs.',
			], 403);
			return false;
		}

		return true;
	}

	private function ensureAuthenticated(Request $request): ?int
	{
		$userId = $request->session('user_id');
		if (empty($userId) || !is_numeric($userId)) {
			$this->json([
				'success' => false,
				'message' => 'Authentication required.',
			], 401);
			return null;
		}

		return (int) $userId;
	}

	private function getRequiredInt(Request $request, string $key): ?int
	{
		$value = $request->get($key);
		if ($value === null || $value === '' || !is_numeric($value)) {
			return null;
		}
		return (int) $value;
	}

	private function getOptionalInt(Request $request, string $key): ?int
	{
		$value = $request->get($key);
		if ($value === null || $value === '') {
			return null;
		}

		if (!is_numeric($value)) {
			return -1;
		}

		return (int) $value;
	}

	private function getPaginationParams(Request $request): array
	{
		$limitRaw = $request->get('limit');
		$offsetRaw = $request->get('offset');
		$searchRaw = $request->get('q');

		$limit = 25;
		if ($limitRaw !== null && $limitRaw !== '' && is_numeric($limitRaw)) {
			$limit = (int) $limitRaw;
		}
		$limit = max(1, min(100, $limit));

		$offset = 0;
		if ($offsetRaw !== null && $offsetRaw !== '' && is_numeric($offsetRaw)) {
			$offset = (int) $offsetRaw;
		}
		$offset = max(0, $offset);

		$search = is_string($searchRaw) ? trim($searchRaw) : '';
		if (strlen($search) > 120) {
			$search = substr($search, 0, 120);
		}

		return [$limit, $offset, $search];
	}

	public function getSummary(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		try {
			$this->json([
				'success' => true,
				'data' => $this->userManagementModel->getSummary(),
			]);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to fetch user management summary.',
			], 500);
		}
	}

	public function getAllUsers(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		[$limit, $offset, $search] = $this->getPaginationParams($request);

		try {
			$this->json([
				'success' => true,
				'data' => $this->userManagementModel->getAllUsers($limit, $offset, $search),
			]);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to fetch users.',
			], 500);
		}
	}

	public function getBannedUsers(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		[$limit, $offset, $search] = $this->getPaginationParams($request);

		try {
			$this->json([
				'success' => true,
				'data' => $this->userManagementModel->getBannedUsers($limit, $offset, $search),
			]);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to fetch banned users.',
			], 500);
		}
	}

	public function getDeletedUsers(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		[$limit, $offset, $search] = $this->getPaginationParams($request);

		try {
			$this->json([
				'success' => true,
				'data' => $this->userManagementModel->getDeletedUsers($limit, $offset, $search),
			]);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to fetch deleted users.',
			], 500);
		}
	}

	public function getPendingReports(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		[$limit, $offset, $search] = $this->getPaginationParams($request);

		try {
			$this->json([
				'success' => true,
				'data' => $this->userManagementModel->getPendingReports($limit, $offset, $search),
			]);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to fetch pending profile reports.',
			], 500);
		}
	}

	public function searchUsers(Request $request): void
	{
		$this->getAllUsers($request);
	}

	public function searchBannedUsers(Request $request): void
	{
		$this->getBannedUsers($request);
	}

	public function searchDeletedUsers(Request $request): void
	{
		$this->getDeletedUsers($request);
	}

	public function searchPendingReports(Request $request): void
	{
		$this->getPendingReports($request);
	}

	public function submitProfileReport(Request $request): void
	{
		$reporterId = $this->ensureAuthenticated($request);
		if ($reporterId === null) {
			return;
		}

		$reportedUserId = $this->getRequiredInt($request, 'reported_user_id');
		$reasonRaw = $request->get('reason');
		$detailsRaw = $request->get('details');

		if ($reportedUserId === null) {
			$this->json([
				'success' => false,
				'message' => 'Valid reported_user_id is required.',
			], 400);
			return;
		}

		$reason = is_string($reasonRaw) ? trim($reasonRaw) : '';
		$details = is_string($detailsRaw) ? trim($detailsRaw) : null;

		if ($reason === '') {
			$this->json([
				'success' => false,
				'message' => 'Reason is required.',
			], 400);
			return;
		}

		try {
			$result = $this->userManagementModel->submitProfileReport($reporterId, $reportedUserId, $reason, $details);

			if (!($result['created'] ?? false)) {
				$this->json([
					'success' => true,
					'data' => [
						'created' => false,
						'reportId' => (int) ($result['reportId'] ?? 0),
					],
					'message' => 'You already have a pending report for this user.',
				]);
				return;
			}

			$this->json([
				'success' => true,
				'data' => [
					'created' => true,
					'reportId' => (int) ($result['reportId'] ?? 0),
				],
				'message' => 'Report submitted successfully.',
			]);
		} catch (InvalidArgumentException $e) {
			$this->json([
				'success' => false,
				'message' => $e->getMessage(),
			], 400);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to submit profile report.',
			], 500);
		}
	}

	public function ignoreReport(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		$reportId = $this->getRequiredInt($request, 'report_id');
		if ($reportId === null) {
			$this->json([
				'success' => false,
				'message' => 'Valid report_id is required.',
			], 400);
			return;
		}

		try {
			$ignored = $this->userManagementModel->ignoreReport($reportId);
			$this->json([
				'success' => $ignored,
				'message' => $ignored ? 'Report ignored successfully.' : 'Pending report not found.',
			], $ignored ? 200 : 404);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to ignore report.',
			], 500);
		}
	}

	public function banUser(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		$userId = $this->getRequiredInt($request, 'user_id');
		if ($userId === null) {
			$this->json([
				'success' => false,
				'message' => 'Valid user_id is required.',
			], 400);
			return;
		}

		$reportId = $this->getOptionalInt($request, 'report_id');
		if ($reportId === -1) {
			$this->json([
				'success' => false,
				'message' => 'report_id must be numeric when provided.',
			], 400);
			return;
		}

		try {
			$banned = $this->userManagementModel->banUser($userId, $reportId);
			$this->json([
				'success' => $banned,
				'message' => $banned ? 'User banned successfully.' : 'User not found.',
			], $banned ? 200 : 404);
		} catch (InvalidArgumentException $e) {
			$this->json([
				'success' => false,
				'message' => $e->getMessage(),
			], 400);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to ban user.',
			], 500);
		}
	}

	public function unbanUser(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		$userId = $this->getRequiredInt($request, 'user_id');
		if ($userId === null) {
			$this->json([
				'success' => false,
				'message' => 'Valid user_id is required.',
			], 400);
			return;
		}

		try {
			$restored = $this->userManagementModel->unbanUser($userId);
			$this->json([
				'success' => $restored,
				'message' => $restored ? 'User unbanned successfully.' : 'Banned user not found.',
			], $restored ? 200 : 404);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to unban user.',
			], 500);
		}
	}

	public function deleteUser(Request $request): void
	{
		$userId = $this->getRequiredInt($request, 'user_id');
		if ($userId === null) {
			$this->json([
				'success' => false,
				'message' => 'Valid user_id is required.',
			], 400);
			return;
		}

		$sessionUserId = $this->ensureAuthenticated($request);
		if ($sessionUserId === null) {
			return;
		}

		$isAdmin = $request->session('user_role') === 'role-admin';
		if (!$isAdmin && $sessionUserId !== $userId) {
			$this->json([
				'success' => false,
				'message' => 'Unauthorized. Only admins can delete other users.',
			], 403);
			return;
		}

		try {
			$deleted = $this->userManagementModel->deleteUser($userId);
			$this->json([
				'success' => $deleted,
				'message' => $deleted ? 'User deleted successfully.' : 'User not found.',
			], $deleted ? 200 : 404);
		} catch (InvalidArgumentException $e) {
			$this->json([
				'success' => false,
				'message' => $e->getMessage(),
			], 400);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to delete user.',
			], 500);
		}
	}

	public function restoreDeletedUser(Request $request): void
	{
		if (!$this->ensureAdmin($request)) {
			return;
		}

		$userId = $this->getRequiredInt($request, 'user_id');
		if ($userId === null) {
			$this->json([
				'success' => false,
				'message' => 'Valid user_id is required.',
			], 400);
			return;
		}

		try {
			$restored = $this->userManagementModel->restoreDeletedUser($userId);
			$this->json([
				'success' => $restored,
				'message' => $restored ? 'Deleted user restored successfully.' : 'Deleted user not found.',
			], $restored ? 200 : 404);
		} catch (\Throwable $e) {
			$this->json([
				'success' => false,
				'message' => 'Failed to restore deleted user.',
			], 500);
		}
	}
}
