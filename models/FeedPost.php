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

        $sql = "SELECT
                    p.id,
                    p.user_id,
                    p.post_type,
                    p.title,
                    p.body,
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
                  AND (
                    p.audience_mode = 'all_roles'
                    OR (p.audience_mode = 'selected_roles' AND p.audience_roles LIKE :role_pattern)
                  )
                ORDER BY p.created_at DESC
                LIMIT {$limit}";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'role_pattern' => '%,' . $viewerRole . ',%'
            ]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch feed posts: ' . $e->getMessage());
        }
    }
}
