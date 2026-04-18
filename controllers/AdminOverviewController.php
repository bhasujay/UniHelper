<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/AdminOverview.php';
require_once dirname(__DIR__, 1) . '/models/User.php';

use app\core\Request;
use app\models\AdminOverview;
use app\models\User;
use InvalidArgumentException;

class AdminOverviewController
{
    private $overviewModel;
    private $userModel;

    public function __construct()
    {
        $this->overviewModel = new AdminOverview();
        $this->userModel = new User();
    }

    public function getSystemOverview(Request $request): void
    {
        if (!$this->ensureGetMethod()) {
            return;
        }

        if (!$this->requireAdmin($request)) {
            return;
        }

        try {
            [$window, $from, $to] = $this->resolveCommonFilters($request);
            $overview = $this->overviewModel->getOverview($window, $from, $to);

            $this->json([
                'success' => true,
                'data' => $overview,
                'meta' => [
                    'window' => $window,
                    'from' => $from,
                    'to' => $to,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to load system overview.',
            ], 500);
        }
    }

    public function getUserActivityList(Request $request): void
    {
        if (!$this->ensureGetMethod()) {
            return;
        }

        if (!$this->requireAdmin($request)) {
            return;
        }

        try {
            [$window, $from, $to] = $this->resolveCommonFilters($request);

            $page = max(1, (int)($request->get('page') ?? 1));
            $limit = max(5, min(100, (int)($request->get('limit') ?? 10)));
            $search = trim((string)($request->get('q') ?? ''));
            $role = trim((string)($request->get('role') ?? ''));

            $payload = $this->overviewModel->getUserActivityList(
                $window,
                $from,
                $to,
                $page,
                $limit,
                $search,
                $role
            );

            $this->json([
                'success' => true,
                'data' => $payload,
                'meta' => [
                    'window' => $window,
                    'from' => $from,
                    'to' => $to,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to load user activity list.',
            ], 500);
        }
    }

    public function getUserActivityDetail(Request $request): void
    {
        if (!$this->ensureGetMethod()) {
            return;
        }

        if (!$this->requireAdmin($request)) {
            return;
        }

        try {
            [$window, $from, $to] = $this->resolveCommonFilters($request);

            $userId = (int)($request->get('user_id') ?? 0);
            if ($userId <= 0) {
                throw new InvalidArgumentException('Valid user_id is required.');
            }

            $payload = $this->overviewModel->getUserActivityDetail($userId, $window, $from, $to);

            if ($payload === null) {
                $this->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
                return;
            }

            $this->json([
                'success' => true,
                'data' => $payload,
                'meta' => [
                    'window' => $window,
                    'from' => $from,
                    'to' => $to,
                ],
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to load user activity details.',
            ], 500);
        }
    }

    private function ensureGetMethod(): bool
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            $this->json([
                'success' => false,
                'message' => 'Method not allowed.',
            ], 405);

            return false;
        }

        return true;
    }

    private function requireAdmin(Request $request): bool
    {
        $userId = (int)($request->session('user_id') ?? 0);
        if ($userId <= 0) {
            $this->json([
                'success' => false,
                'message' => 'Not authenticated.',
            ], 401);

            return false;
        }

        $user = $this->userModel->findById($userId);
        if (!$user || (string)($user->role ?? '') !== 'role-admin') {
            $this->json([
                'success' => false,
                'message' => 'Access denied. Administrator role required.',
            ], 403);

            return false;
        }

        return true;
    }

    private function resolveCommonFilters(Request $request): array
    {
        $window = strtolower(trim((string)($request->get('window') ?? 'active')));
        if (!in_array($window, ['active', 'archived'], true)) {
            throw new InvalidArgumentException('Invalid window filter. Use active or archived.');
        }

        $from = $this->normalizeDate($request->get('from'));
        $to = $this->normalizeDate($request->get('to'));

        if ($from !== null && $to !== null && $from > $to) {
            throw new InvalidArgumentException('The from date cannot be greater than the to date.');
        }

        return [$window, $from, $to];
    }

    private function normalizeDate($value): ?string
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return null;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $raw);
        $errors = \DateTime::getLastErrors();
        $warningCount = is_array($errors) ? (int)($errors['warning_count'] ?? 0) : 0;
        $errorCount = is_array($errors) ? (int)($errors['error_count'] ?? 0) : 0;

        if (!$date || $warningCount > 0 || $errorCount > 0) {
            throw new InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
        }

        return $date->format('Y-m-d');
    }

    private function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
