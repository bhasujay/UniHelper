<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/models/base-model.php';

use PDO;
use PDOException;
use Exception;

class FeedPost extends BaseModel
{
    protected $table = 'feed_posts';

    private const VALID_TYPES = ['announcement', 'event', 'general'];
    private const VALID_AUDIENCE_MODES = ['all_roles', 'selected_roles'];

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
