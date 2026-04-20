<?php

namespace app\models;

use app\core\Database;

require_once dirname(__DIR__) . '/models/base-model.php';

class search extends BaseModel
{
    private const VALID_ROLES = ['role-applicant', 'role-undergrad', 'role-profile', 'role-admin'];

    public function __construct()
    {
        parent::__construct();
    }

    // searching for questions, answers, tags
    public function qa_search($query, $index)
    {
        $results = [];

        // Determine if this is a tag search (query starts with #)
        $isTagSearch = str_starts_with($query, '#');

        if ($isTagSearch) {
            // ── TIER 1: Tag search ──────────────────────────────────
            // Strip the # symbol and search for exact tag match (case-insensitive)
            $tagName = substr($query, 1);

            if ($tagName !== '') {
                $tagResults = $this->searchByTag($tagName);
                $results = array_merge($results, $tagResults);
            }
        } else {
            // ── TIER 2: Title search ────────────────────────────────
            $titleResults = $this->searchByTitle($query);
            $results = array_merge($results, $titleResults);

            // ── TIER 3: Body search ─────────────────────────────────
            // Collect question IDs already found from title search to avoid duplicates
            $foundQuestionIds = array_map(function ($r) {
                return $r['deeplink_ref'];
            }, $results);

            $bodyResults = $this->searchByBody($query, $foundQuestionIds);
            $results = array_merge($results, $bodyResults);

            // ── TIER 4: Answer search ───────────────────────────────
            $answerResults = $this->searchByAnswers($query);
            $results = array_merge($results, $answerResults);
        }

        return $results;
    }

    // ── TIER 1 ─────────────────────────────────────────────────
    // Tag search: exact case-insensitive match on tag_name,
    // then find all questions linked to that tag
    private function searchByTag($tagName)
    {
        $sql = "
            SELECT q.q_id, q.question, q.text,
                   COALESCE(q.last_modified, q.added_time) AS timestamp
            FROM questions q
            JOIN qa_tag qt ON q.q_id = qt.q_id
            JOIN tags t    ON t.tag_id = qt.tag_id
            WHERE LOWER(t.tag_name) = LOWER(:tagName)
                            AND q.status IN ('normal', 'flagged')
                            AND NOT EXISTS (
                                        SELECT 1
                                        FROM reports r
                                        WHERE r.q_id = q.q_id
                                            AND r.status = 'resolved'
                                            AND r.action_taken = 'removed'
                            )
            ORDER BY q.vote_count DESC, q.added_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tagName', $tagName, \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'type'          => 'question',
                'questionTitle' => $row['question'],
                'questionText'  => $row['text'],
                'deeplink_ref'  => (string)$row['q_id'],
                'timestamp'     => $this->formatTimestamp($row['timestamp'])
            ];
        }
        return $results;
    }

    // ── TIER 2 ─────────────────────────────────────────────────
    // Title search: LIKE match (case-insensitive) on question title
    private function searchByTitle($query)
    {
        $sql = "
            SELECT q_id, question,
                   COALESCE(last_modified, added_time) AS timestamp
            FROM questions
            WHERE LOWER(question) LIKE LOWER(:query)
                            AND status IN ('normal', 'flagged')
                            AND NOT EXISTS (
                                        SELECT 1
                                        FROM reports r
                                        WHERE r.q_id = questions.q_id
                                            AND r.status = 'resolved'
                                            AND r.action_taken = 'removed'
                            )
            ORDER BY vote_count DESC, added_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', '%' . $query . '%', \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'type'          => 'question',
                'questionTitle' => $row['question'],
                'questionText'  => '',           // empty — matched on title
                'deeplink_ref'  => (string)$row['q_id'],
                'timestamp'     => $this->formatTimestamp($row['timestamp'])
            ];
        }
        return $results;
    }

    // ── TIER 3 ─────────────────────────────────────────────────
    // Body search: LIKE match (case-insensitive) on question body/text
    private function searchByBody($query, $excludeQuestionIds = [])
    {
        $sql = "
            SELECT q_id, question, text,
                   COALESCE(last_modified, added_time) AS timestamp
            FROM questions
            WHERE LOWER(text) LIKE LOWER(:query)
              AND status IN ('normal', 'flagged')
                            AND NOT EXISTS (
                                        SELECT 1
                                        FROM reports r
                                        WHERE r.q_id = questions.q_id
                                            AND r.status = 'resolved'
                                            AND r.action_taken = 'removed'
                            )
        ";

        // Exclude question IDs already found in the title tier
        if (!empty($excludeQuestionIds)) {
            // Use integer placeholders for safety
            $placeholders = [];
            foreach ($excludeQuestionIds as $i => $id) {
                $placeholders[] = ':excl' . $i;
            }
            $sql .= " AND q_id NOT IN (" . implode(',', $placeholders) . ")";
        }

        $sql .= " ORDER BY vote_count DESC, added_time DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', '%' . $query . '%', \PDO::PARAM_STR);

        // Bind the exclusion IDs
        if (!empty($excludeQuestionIds)) {
            foreach ($excludeQuestionIds as $i => $id) {
                $stmt->bindValue(':excl' . $i, (int)$id, \PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'type'          => 'question',
                'questionTitle' => $row['question'],
                'questionText'  => $row['text'],
                'deeplink_ref'  => (string)$row['q_id'],
                'timestamp'     => $this->formatTimestamp($row['timestamp'])
            ];
        }
        return $results;
    }

    // ── TIER 4 ─────────────────────────────────────────────────
    // Answer search: LIKE match (case-insensitive) on answer text,
    // then join to get the parent question's title
    private function searchByAnswers($query)
    {
        $sql = "
            SELECT a.a_id, a.q_id, a.text AS answer_text,
                   a.added_time AS timestamp,
                   q.question AS question_title
            FROM answers a
            JOIN questions q ON a.q_id = q.q_id
            WHERE LOWER(a.text) LIKE LOWER(:query)
                            AND a.status IN ('normal', 'flagged')
                            AND q.status IN ('normal', 'flagged')
                            AND NOT EXISTS (
                                        SELECT 1
                                        FROM reports ra
                                        WHERE ra.a_id = a.a_id
                                            AND ra.status = 'resolved'
                                            AND ra.action_taken = 'removed'
                            )
                            AND NOT EXISTS (
                                        SELECT 1
                                        FROM reports rq
                                        WHERE rq.q_id = q.q_id
                                            AND rq.status = 'resolved'
                                            AND rq.action_taken = 'removed'
                            )
            ORDER BY a.added_time DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':query', '%' . $query . '%', \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $results = [];
        foreach ($rows as $row) {
            $results[] = [
                'type'          => 'answer',
                'questionTitle' => $row['question_title'],
                'answerText'    => $row['answer_text'],
                'deeplink_ref'  => $row['q_id'] . ',' . $row['a_id'],
                'timestamp'     => $this->formatTimestamp($row['timestamp'])
            ];
        }
        return $results;
    }

    // ── Helper ─────────────────────────────────────────────────
    // Convert a DB timestamp to ISO-8601 string for the frontend
    private function formatTimestamp($timestamp)
    {
        if (!$timestamp) {
            return null;
        }
        // Return ISO-8601 so the front-end can use getRelativeTime()
        return date('c', strtotime($timestamp));
    }

    // searching for users by first name / last name / full name
    // contract: [{user_id, name, profile_picture, role}, ...]
    public function user_search($query, $index, $excludeUserId = null)
    {
        $query = trim((string)$query);
        if ($query === '') {
            return [];
        }

        $page = max(0, (int)$index);
        $limit = 20;
        $offset = $page * $limit;

        $like = '%' . $query . '%';
        $prefix = $query . '%';

        $sql = "SELECT
                    u.id AS user_id,
                    CONCAT(u.first_name, ' ', u.last_name) AS name,
                    u.profile_picture,
                    u.role
                FROM users u
                WHERE u.public = 1
                  AND (
                        LOWER(CONCAT(u.first_name, ' ', u.last_name)) LIKE LOWER(:like_full)
                     OR LOWER(u.first_name) LIKE LOWER(:like_first)
                     OR LOWER(u.last_name) LIKE LOWER(:like_last)
                  )";

        $params = [
            ':like_full' => $like,
            ':like_first' => $like,
            ':like_last' => $like,
            ':exact' => $query,
            ':prefix_full' => $prefix,
            ':prefix_first' => $prefix,
            ':prefix_last' => $prefix,
        ];

        if ($excludeUserId !== null) {
            $sql .= " AND u.id <> :excludeUserId";
            $params[':excludeUserId'] = (int)$excludeUserId;
        }

        $sql .= "
                ORDER BY
                    CASE
                        WHEN LOWER(CONCAT(u.first_name, ' ', u.last_name)) = LOWER(:exact) THEN 0
                        WHEN LOWER(CONCAT(u.first_name, ' ', u.last_name)) LIKE LOWER(:prefix_full) THEN 1
                        WHEN LOWER(u.first_name) LIKE LOWER(:prefix_first) THEN 2
                        WHEN LOWER(u.last_name) LIKE LOWER(:prefix_last) THEN 3
                        ELSE 4
                    END,
                    u.first_name ASC,
                    u.last_name ASC
                LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // searching for posts in the feed
    public function feed_search($query, $index, $viewerRole = '', $viewerId = 0)
    {
        $query = trim((string)$query);
        if ($query === '') {
            return [];
        }

        $page = max(0, (int)$index);
        $limit = 20;
        $offset = $page * $limit;
        $viewerRole = trim((string)$viewerRole);
        $viewerId = (int)$viewerId;
        $canViewAllPosts = in_array($viewerRole, ['role-profile', 'role-admin'], true);

        $visibilityClause = $canViewAllPosts
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
                FROM feed_posts p
                INNER JOIN users u ON u.id = p.user_id
                WHERE p.is_deleted = 0
                  AND p.deleted_at IS NULL
                                        AND {$visibilityClause}
                  AND (
                                LOWER(p.title) LIKE LOWER(:query_title)
                            OR LOWER(p.body) LIKE LOWER(:query_body)
                  )
                ORDER BY p.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        if (!$canViewAllPosts) {
                        $rolePattern = '%,' . $viewerRole . ',%';
                        if ($viewerRole === '') {
                                $rolePattern = ',,,';
                        }
                        $stmt->bindValue(':role_pattern', $rolePattern, \PDO::PARAM_STR);
                }

                $searchLike = '%' . $query . '%';
                $stmt->bindValue(':query_title', $searchLike, \PDO::PARAM_STR);
                $stmt->bindValue(':query_body', $searchLike, \PDO::PARAM_STR);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                $likeStats = $this->loadPostLikeStats($rows, $viewerId);

        $results = [];
        foreach ($rows as $row) {
            $roles = $this->parseAudienceRoles($row['audience_roles'] ?? null);
            $authorName = trim(((string)($row['first_name'] ?? '')) . ' ' . ((string)($row['last_name'] ?? '')));
            if ($authorName === '') {
                $authorName = 'User #' . (int)$row['user_id'];
            }

                    $sourceId = (int)$row['id'];
                    $likeCount = (int)($likeStats[$sourceId]['like_count'] ?? 0);
                    $likedByViewer = (bool)($likeStats[$sourceId]['liked_by_viewer'] ?? false);

            $results[] = [
                        'id' => 'post-' . $sourceId,
                'source' => 'post',
                        'source_id' => $sourceId,
                'post_type' => (string)$row['post_type'],
                'title' => (string)$row['title'],
                'body' => (string)$row['body'],
                'image_path' => (string)($row['image_path'] ?? ''),
                'created_at' => (string)$row['created_at'],
                'audience_label' => $this->audienceLabel((string)($row['audience_mode'] ?? 'all_roles'), $roles),
                'author_id' => (int)($row['user_id'] ?? 0),
                'author_name' => $authorName,
                'author_role_label' => $this->roleLabel((string)($row['author_role'] ?? '')),
                'can_manage' => ($viewerRole === 'role-admin') || ($viewerId > 0 && (int)$row['user_id'] === $viewerId),
                'like_count' => $likeCount,
                'liked_by_viewer' => $likedByViewer,
                'meta' => [
                    'roles' => $roles,
                ],
            ];
        }

        return $results;
    }

    // searching for sessions
    public function session_search($query, $index)
    {
        return [];
    }

    private function loadPostLikeStats(array $rows, int $viewerId): array
    {
        $postIds = [];
        foreach ($rows as $row) {
            $postId = (int)($row['id'] ?? 0);
            if ($postId > 0) {
                $postIds[$postId] = $postId;
            }
        }

        if (empty($postIds)) {
            return [];
        }

        $ids = array_values($postIds);
        $stats = [];
        foreach ($ids as $id) {
            $stats[$id] = [
                'like_count' => 0,
                'liked_by_viewer' => false,
            ];
        }

        $placeholders = [];
        $params = [];
        foreach ($ids as $index => $id) {
            $token = ':id_' . $index;
            $placeholders[] = $token;
            $params['id_' . $index] = $id;
        }
        $inClause = implode(', ', $placeholders);

        try {
            $countSql = "SELECT source_id, COUNT(*) AS like_count
                         FROM feed_likes
                         WHERE source_type = 'post'
                           AND source_id IN ({$inClause})
                         GROUP BY source_id";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $countRows = $countStmt->fetchAll(\PDO::FETCH_ASSOC);

            foreach ($countRows as $row) {
                $id = (int)($row['source_id'] ?? 0);
                if (isset($stats[$id])) {
                    $stats[$id]['like_count'] = (int)($row['like_count'] ?? 0);
                }
            }

            if ($viewerId > 0) {
                $viewerSql = "SELECT source_id
                              FROM feed_likes
                              WHERE source_type = 'post'
                                AND user_id = :viewer_id
                                AND source_id IN ({$inClause})";
                $viewerStmt = $this->db->prepare($viewerSql);
                $viewerParams = array_merge(['viewer_id' => $viewerId], $params);
                $viewerStmt->execute($viewerParams);
                $viewerRows = $viewerStmt->fetchAll(\PDO::FETCH_ASSOC);

                foreach ($viewerRows as $row) {
                    $id = (int)($row['source_id'] ?? 0);
                    if (isset($stats[$id])) {
                        $stats[$id]['liked_by_viewer'] = true;
                    }
                }
            }
        } catch (\PDOException $e) {
            // Keep feed search available even when likes migration has not been applied.
            return $stats;
        }

        return $stats;
    }

    private function parseAudienceRoles($serialized)
    {
        $value = (string)($serialized ?? '');
        if ($value === '') {
            return [];
        }

        $parts = array_filter(array_map('trim', explode(',', trim($value, ','))));
        $result = [];
        foreach ($parts as $part) {
            if (in_array($part, self::VALID_ROLES, true)) {
                $result[$part] = true;
            }
        }

        return array_keys($result);
    }

    private function roleLabel($role)
    {
        $labels = [
            'role-applicant' => 'Applicant',
            'role-undergrad' => 'Undergraduate',
            'role-profile' => 'Profile',
            'role-admin' => 'Admin',
        ];

        return $labels[$role] ?? 'User';
    }

    private function audienceLabel($mode, $roles)
    {
        if ($mode !== 'selected_roles') {
            return 'All Roles';
        }

        if (empty($roles)) {
            return 'Selected Roles';
        }

        return implode(', ', array_map(function ($role) {
            return $this->roleLabel($role);
        }, $roles));
    }
}
