<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/models/base-model.php';
require_once dirname(__DIR__, 1) . '/models/notify.php';

use PDO;
use PDOException;
use Exception;

class FeedPost extends BaseModel
{
    protected $table = 'feed_posts';
    private $notifyModel;

    private const VALID_TYPES = ['announcement', 'event', 'general'];
    private const VALID_AUDIENCE_MODES = ['all_roles', 'selected_roles'];
    private const VALID_SOURCE_TYPES = ['post', 'session'];
    private const LIKES_TABLE = 'feed_likes';

    public function __construct()
    {
        parent::__construct();
        $this->notifyModel = new Notify();
    }

    public function createPost(array $data): int
    {
        if (!in_array($data['post_type'] ?? '', self::VALID_TYPES, true)) {
            throw new Exception('Invalid post type.');
        }

        if (!in_array($data['audience_mode'] ?? '', self::VALID_AUDIENCE_MODES, true)) {
            throw new Exception('Invalid audience mode.');
        }

        return (int)$this->create($data);
    }

    public function pushNotifications(array $recipientIds, string $message, string $type = 'other', ?string $link = null): int
    {
        $normalizedIds = [];
        foreach ($recipientIds as $recipientId) {
            $id = (int)$recipientId;
            if ($id > 0) {
                $normalizedIds[$id] = true;
            }
        }

        $normalizedIds = array_keys($normalizedIds);
        if (empty($normalizedIds)) {
            return 0;
        }

        $sentCount = 0;
        foreach ($normalizedIds as $userId) {
            $this->notifyModel->insertNotification($userId, $message, $type, $link);
            $sentCount++;
        }

        return $sentCount;
    }

    public function isValidSourceType(string $sourceType): bool
    {
        return in_array($sourceType, self::VALID_SOURCE_TYPES, true);
    }

    public function toggleLike(int $userId, string $sourceType, int $sourceId): array
    {
        if (!$this->isValidSourceType($sourceType)) {
            throw new Exception('Invalid like source type.');
        }

        if ($sourceId <= 0) {
            throw new Exception('Invalid source ID.');
        }

        try {
            $selectSql = "SELECT id
                          FROM " . self::LIKES_TABLE . "
                          WHERE user_id = :user_id
                            AND source_type = :source_type
                            AND source_id = :source_id
                          LIMIT 1";
            $selectStmt = $this->db->prepare($selectSql);
            $selectStmt->execute([
                'user_id' => $userId,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            $existingId = $selectStmt->fetchColumn();
            $likedByViewer = false;

            if ($existingId !== false) {
                $deleteSql = "DELETE FROM " . self::LIKES_TABLE . "
                              WHERE id = :id";
                $deleteStmt = $this->db->prepare($deleteSql);
                $deleteStmt->execute(['id' => (int)$existingId]);
                $likedByViewer = false;
            } else {
                $insertSql = "INSERT INTO " . self::LIKES_TABLE . " (user_id, source_type, source_id)
                              VALUES (:user_id, :source_type, :source_id)";
                $insertStmt = $this->db->prepare($insertSql);
                $insertStmt->execute([
                    'user_id' => $userId,
                    'source_type' => $sourceType,
                    'source_id' => $sourceId,
                ]);
                $likedByViewer = true;
            }

            return [
                'liked_by_viewer' => $likedByViewer,
                'like_count' => $this->getLikeCount($sourceType, $sourceId),
            ];
        } catch (PDOException $e) {
            throw new Exception('Failed to toggle like: ' . $e->getMessage());
        }
    }

    public function getLikeCount(string $sourceType, int $sourceId): int
    {
        if (!$this->isValidSourceType($sourceType) || $sourceId <= 0) {
            return 0;
        }

        try {
            $sql = "SELECT COUNT(*)
                    FROM " . self::LIKES_TABLE . "
                    WHERE source_type = :source_type
                      AND source_id = :source_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);

            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch like count: ' . $e->getMessage());
        }
    }

    public function deleteLikesForSource(string $sourceType, int $sourceId): bool
    {
        if (!$this->isValidSourceType($sourceType) || $sourceId <= 0) {
            return false;
        }

        try {
            $sql = "DELETE FROM " . self::LIKES_TABLE . "
                    WHERE source_type = :source_type
                      AND source_id = :source_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ]);
        } catch (PDOException $e) {
            throw new Exception('Failed to delete likes: ' . $e->getMessage());
        }
    }

    public function getLikeStatsForItems(array $items, int $viewerId): array
    {
        $indexedItems = [];
        foreach ($items as $item) {
            $sourceType = strtolower(trim((string)($item['source'] ?? '')));
            $sourceId = (int)($item['source_id'] ?? 0);
            if (!$this->isValidSourceType($sourceType) || $sourceId <= 0) {
                continue;
            }

            $key = $this->makeLikeKey($sourceType, $sourceId);
            $indexedItems[$key] = [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ];
        }

        if (empty($indexedItems)) {
            return [];
        }

        $entries = array_values($indexedItems);
        $stats = [];
        foreach ($entries as $entry) {
            $stats[$this->makeLikeKey($entry['source_type'], $entry['source_id'])] = [
                'like_count' => 0,
                'liked_by_viewer' => false,
            ];
        }

        try {
            $whereParts = [];
            $params = [];
            foreach ($entries as $index => $entry) {
                $whereParts[] = "(source_type = :source_type_{$index} AND source_id = :source_id_{$index})";
                $params['source_type_' . $index] = $entry['source_type'];
                $params['source_id_' . $index] = $entry['source_id'];
            }

            $whereClause = implode(' OR ', $whereParts);

            $countSql = "SELECT source_type, source_id, COUNT(*) AS like_count
                         FROM " . self::LIKES_TABLE . "
                         WHERE {$whereClause}
                         GROUP BY source_type, source_id";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $countRows = $countStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($countRows as $row) {
                $key = $this->makeLikeKey((string)$row['source_type'], (int)$row['source_id']);
                if (isset($stats[$key])) {
                    $stats[$key]['like_count'] = (int)$row['like_count'];
                }
            }

            if ($viewerId > 0) {
                $viewerSql = "SELECT source_type, source_id
                              FROM " . self::LIKES_TABLE . "
                              WHERE user_id = :viewer_id
                                AND ({$whereClause})";
                $viewerStmt = $this->db->prepare($viewerSql);
                $viewerParams = array_merge(['viewer_id' => $viewerId], $params);
                $viewerStmt->execute($viewerParams);
                $viewerRows = $viewerStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($viewerRows as $row) {
                    $key = $this->makeLikeKey((string)$row['source_type'], (int)$row['source_id']);
                    if (isset($stats[$key])) {
                        $stats[$key]['liked_by_viewer'] = true;
                    }
                }
            }

            return $stats;
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch like stats: ' . $e->getMessage());
        }
    }

    private function makeLikeKey(string $sourceType, int $sourceId): string
    {
        return $sourceType . '-' . $sourceId;
    }

    public function createPostLikeNotification(int $postOwnerId, int $actorUserId, string $actorName, int $postId, string $postTitle = ''): int
    {
        if ($postOwnerId <= 0 || $actorUserId <= 0 || $postId <= 0) {
            return 0;
        }

        if ($postOwnerId === $actorUserId) {
            return 0;
        }

        $safeActorName = trim($actorName);
        if ($safeActorName === '') {
            $safeActorName = 'Someone';
        }

        $safeTitle = trim($postTitle);
        if ($safeTitle !== '') {
            $safeTitle = preg_replace('/\s+/', ' ', $safeTitle);
            if (mb_strlen($safeTitle) > 80) {
                $safeTitle = mb_substr($safeTitle, 0, 77) . '...';
            }
        }

        $message = $safeTitle !== ''
            ? $safeActorName . ' liked your post: "' . $safeTitle . '"'
            : $safeActorName . ' liked your post.';

        $deepLink = '/unihelper/announcements?source=post&post=' . $postId;

        return $this->notifyModel->insertNotification($postOwnerId, $message, 'other', $deepLink);
    }

    public function getVisiblePostsForRole(string $viewerRole, int $limit = 100): array
    {
        $limit = max(1, min($limit, 200));
        $isAdmin = $viewerRole === 'role-admin';

        $visibilityClause = $isAdmin
            ? '1 = 1'
            : "(
                    p.audience_mode = 'all_roles'
                    OR (p.audience_mode = 'selected_roles' AND p.audience_roles LIKE :role_pattern)
               )";

        $sql = "SELECT
                    p.id,
                    p.user_id,
                    p.post_type,
                    p.title,
                    p.body,
                    p.image_path,
                    p.audience_mode,
                    p.audience_roles,
                    p.created_at,
                    u.first_name,
                    u.last_name,
                    u.role AS author_role
                FROM {$this->table} p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.is_deleted = 0
                  AND p.deleted_at IS NULL
                  AND {$visibilityClause}
                ORDER BY p.created_at DESC
                LIMIT {$limit}";

        try {
            $stmt = $this->db->prepare($sql);

            $params = [];
            if (!$isAdmin) {
                $rolePattern = '%,' . trim((string)$viewerRole) . ',%';
                if (trim((string)$viewerRole) === '') {
                    $rolePattern = ',,,';
                }
                $params['role_pattern'] = $rolePattern;
            }

            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch feed posts: ' . $e->getMessage());
        }
    }

    public function findActivePostById(int $postId): ?array
    {
        $sql = "SELECT
                    p.id,
                    p.user_id,
                    p.post_type,
                    p.title,
                    p.body,
                    p.image_path,
                    p.audience_mode,
                    p.audience_roles,
                    p.created_at,
                    p.updated_at,
                    u.first_name,
                    u.last_name,
                    u.role AS author_role
                FROM {$this->table} p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.id = :id
                  AND p.is_deleted = 0
                  AND p.deleted_at IS NULL
                LIMIT 1";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id' => $postId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            throw new Exception('Failed to load feed post: ' . $e->getMessage());
        }
    }

    public function updatePost(int $postId, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        if (isset($data['post_type']) && !in_array($data['post_type'], self::VALID_TYPES, true)) {
            throw new Exception('Invalid post type.');
        }

        if (isset($data['audience_mode']) && !in_array($data['audience_mode'], self::VALID_AUDIENCE_MODES, true)) {
            throw new Exception('Invalid audience mode.');
        }

        $setParts = [];
        $params = ['id' => $postId];

        foreach ($data as $column => $value) {
            $setParts[] = "{$column} = :{$column}";
            $params[$column] = $value;
        }

        $sql = "UPDATE {$this->table}
                SET " . implode(', ', $setParts) . "
                WHERE id = :id
                  AND is_deleted = 0
                  AND deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception('Failed to update feed post: ' . $e->getMessage());
        }
    }

    public function softDeletePost(int $postId): bool
    {
        $sql = "UPDATE {$this->table}
                SET is_deleted = 1,
                    deleted_at = NOW()
                WHERE id = :id
                  AND is_deleted = 0
                  AND deleted_at IS NULL";

        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute(['id' => $postId]);
        } catch (PDOException $e) {
            throw new Exception('Failed to delete feed post: ' . $e->getMessage());
        }
    }
}
