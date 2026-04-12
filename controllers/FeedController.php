<?php

namespace app\controllers;

require_once dirname(__DIR__, 1) . '/models/FeedPost.php';
require_once dirname(__DIR__, 1) . '/models/Session_model.php';
require_once dirname(__DIR__, 1) . '/models/User.php';

use app\core\Request;
use app\models\FeedPost;
use app\models\Session_model;
use app\models\User;

class FeedController
{
    private const VALID_ROLES = ['role-applicant', 'role-undergrad', 'role-profile', 'role-admin'];
    private const VALID_TYPES = ['announcement', 'event', 'general'];

    private $feedPostModel;
    private $sessionModel;
    private $viewer;

    public function __construct()
    {
        if (!isset($_SESSION['user_id'])) {
            header('Location: /UniHelper/home');
            exit;
        }

        $userModel = new User();
        $this->viewer = $userModel->findById($_SESSION['user_id']);

        if (!$this->viewer) {
            session_destroy();
            header('Location: /UniHelper/register');
            exit;
        }

        $this->feedPostModel = new FeedPost();
        $this->sessionModel = new Session_model();
    }

    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }

    public function getFeed(Request $request): void
    {
        try {
            $page = max(1, (int)($request->get('page') ?? 1));
            $limit = max(5, min(20, (int)($request->get('limit') ?? 8)));
            $offset = ($page - 1) * $limit;

            // Pull a larger window first, then paginate after merging different sources.
            $fetchWindow = max(40, $offset + $limit + 20);

            $feedPosts = $this->feedPostModel->getVisiblePostsForRole((string)$this->viewer->role, $fetchWindow);
            $sessions = $this->sessionModel->findVisibleForFeed(
                (string)$this->viewer->role,
                (string)($this->viewer->University ?? ''),
                $fetchWindow
            );

            $items = [];

            foreach ($feedPosts as $post) {
                $items[] = $this->normalizeFeedPost($post);
            }

            foreach ($sessions as $session) {
                $items[] = $this->normalizeSessionPost($session);
            }

            usort($items, function ($left, $right) {
                return strtotime((string)($right['created_at'] ?? '')) <=> strtotime((string)($left['created_at'] ?? ''));
            });

            $total = count($items);
            $paged = array_slice($items, $offset, $limit);

            $this->json([
                'success' => true,
                'data' => $paged,
                'page' => $page,
                'limit' => $limit,
                'has_more' => ($offset + $limit) < $total,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to load feed items.'
            ], 500);
        }
    }

    public function createPost(Request $request): void
    {
        $title = trim((string)($request->get('title') ?? ''));
        $body = trim((string)($request->get('body') ?? ''));
        $postType = trim((string)($request->get('post_type') ?? 'announcement'));
        $audienceMode = trim((string)($request->get('audience_mode') ?? 'all_roles'));
        $audienceRolesInput = $request->get('audience_roles');

        if ($title === '') {
            $this->json(['success' => false, 'message' => 'Title is required.'], 422);
            return;
        }

        if (mb_strlen($title) > 255) {
            $this->json(['success' => false, 'message' => 'Title must not exceed 255 characters.'], 422);
            return;
        }

        if ($body === '') {
            $this->json(['success' => false, 'message' => 'Content is required.'], 422);
            return;
        }

        if (!in_array($postType, self::VALID_TYPES, true)) {
            $this->json(['success' => false, 'message' => 'Invalid post type.'], 422);
            return;
        }

        if (!in_array($audienceMode, ['all_roles', 'selected_roles'], true)) {
            $this->json(['success' => false, 'message' => 'Invalid audience selection.'], 422);
            return;
        }

        $roles = $this->sanitizeRoleList($audienceRolesInput);

        if ($audienceMode === 'selected_roles' && empty($roles)) {
            $this->json(['success' => false, 'message' => 'Select at least one audience role.'], 422);
            return;
        }

        $audienceRoles = $audienceMode === 'selected_roles'
            ? $this->serializeAudienceRoles($roles)
            : null;

        try {
            $newId = $this->feedPostModel->createPost([
                'user_id' => (int)$this->viewer->id,
                'post_type' => $postType,
                'title' => $title,
                'body' => $body,
                'audience_mode' => $audienceMode,
                'audience_roles' => $audienceRoles,
            ]);

            $this->json([
                'success' => true,
                'message' => 'Post published successfully.',
                'id' => $newId,
            ]);
        } catch (\Throwable $e) {
            $this->json([
                'success' => false,
                'message' => 'Failed to publish post. Please run the feed table migration first.'
            ], 500);
        }
    }

    private function normalizeFeedPost(array $post): array
    {
        $roles = $this->parseAudienceRoles($post['audience_roles'] ?? null);
        $audienceLabel = ($post['audience_mode'] ?? 'all_roles') === 'selected_roles'
            ? implode(', ', array_map([$this, 'roleLabel'], $roles))
            : 'All Roles';

        $authorName = trim(((string)($post['first_name'] ?? '')) . ' ' . ((string)($post['last_name'] ?? '')));
        if ($authorName === '') {
            $authorName = 'User #' . (int)$post['user_id'];
        }

        return [
            'id' => 'post-' . (int)$post['id'],
            'source' => 'post',
            'source_id' => (int)$post['id'],
            'post_type' => (string)$post['post_type'],
            'title' => (string)$post['title'],
            'body' => (string)$post['body'],
            'created_at' => (string)$post['created_at'],
            'audience_label' => $audienceLabel,
            'author_name' => $authorName,
            'author_role_label' => $this->roleLabel((string)($post['author_role'] ?? '')),
            'meta' => [
                'roles' => $roles,
            ],
        ];
    }

    private function normalizeSessionPost(array $session): array
    {
        $authorName = trim(((string)($session['first_name'] ?? '')) . ' ' . ((string)($session['last_name'] ?? '')));
        if ($authorName === '') {
            $authorName = 'User #' . (int)$session['user_id'];
        }

        $sessionAudience = (string)($session['audience'] ?? '') === 'my_university'
            ? 'My University'
            : 'All Universities';

        return [
            'id' => 'session-' . (int)$session['id'],
            'source' => 'session',
            'source_id' => (int)$session['id'],
            'post_type' => 'session',
            'title' => (string)$session['title'],
            'body' => (string)($session['description'] ?? ''),
            'created_at' => (string)($session['feed_created_at'] ?? ''),
            'audience_label' => $sessionAudience,
            'author_name' => $authorName,
            'author_role_label' => $this->roleLabel((string)($session['creator_role'] ?? '')),
            'meta' => [
                'subject' => (string)($session['subject'] ?? ''),
                'date' => (string)($session['date'] ?? ''),
                'time' => (string)($session['time'] ?? ''),
                'duration' => (string)($session['duration'] ?? ''),
                'session_link' => (string)($session['session_link'] ?? ''),
                'tags' => (string)($session['tags'] ?? ''),
                'source_link' => '/UniHelper/peer-learning',
            ],
        ];
    }

    private function sanitizeRoleList($rawRoles): array
    {
        if (is_array($rawRoles)) {
            $roles = $rawRoles;
        } elseif (is_string($rawRoles) && $rawRoles !== '') {
            $roles = explode(',', $rawRoles);
        } else {
            $roles = [];
        }

        $sanitized = [];
        foreach ($roles as $role) {
            $normalized = trim((string)$role);
            if ($normalized !== '' && in_array($normalized, self::VALID_ROLES, true)) {
                $sanitized[$normalized] = true;
            }
        }

        return array_keys($sanitized);
    }

    private function serializeAudienceRoles(array $roles): ?string
    {
        if (empty($roles)) {
            return null;
        }

        return ',' . implode(',', $roles) . ',';
    }

    private function parseAudienceRoles(?string $serialized): array
    {
        if ($serialized === null || $serialized === '') {
            return [];
        }

        $parts = array_filter(array_map('trim', explode(',', trim($serialized, ','))));
        $result = [];
        foreach ($parts as $part) {
            if (in_array($part, self::VALID_ROLES, true)) {
                $result[$part] = true;
            }
        }

        return array_keys($result);
    }

    private function roleLabel(string $role): string
    {
        $labels = [
            'role-applicant' => 'Applicant',
            'role-undergrad' => 'Undergraduate',
            'role-profile' => 'Profile',
            'role-admin' => 'Admin',
        ];

        return $labels[$role] ?? 'User';
    }
}
