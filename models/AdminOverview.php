<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/core/Database.php';

use app\core\Database;
use PDO;
use PDOException;
use Exception;

class AdminOverview
{
    private $db;
    private $tableExistsCache = [];
    private const DEFAULT_DURATION_HOURS = 1.0;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getOverview(string $window, ?string $from, ?string $to): array
    {
        $summary = $this->getSummaryCounts($window, $from, $to);
        $roleDistribution = $this->getRoleDistribution($from, $to);
        $activityDistribution = $this->getActivityDistributionFromSummary($summary);

        $topUsersPayload = $this->getUserActivityList($window, $from, $to, 1, 5, '', '');

        return [
            'summary' => $summary,
            'role_distribution' => $roleDistribution,
            'activity_distribution' => $activityDistribution,
            'top_users' => $topUsersPayload['items'],
        ];
    }

    public function getUserActivityList(
        string $window,
        ?string $from,
        ?string $to,
        int $page,
        int $limit,
        string $search,
        string $role
    ): array {
        $page = max(1, $page);
        $limit = max(5, min(100, $limit));
        $offset = ($page - 1) * $limit;

        $whereParts = ['1 = 1'];
        $whereParams = [];

        if ($role !== '') {
            $whereParts[] = 'u.role = :filter_role';
            $whereParams['filter_role'] = $role;
        }

        if ($search !== '') {
            $searchLike = '%' . $search . '%';
            $whereParts[] = '(
                u.first_name LIKE :filter_search_first_name
                OR u.last_name LIKE :filter_search_last_name
                OR u.email LIKE :filter_search_email
                OR u.phone LIKE :filter_search_phone
            )';
            $whereParams['filter_search_first_name'] = $searchLike;
            $whereParams['filter_search_last_name'] = $searchLike;
            $whereParams['filter_search_email'] = $searchLike;
            $whereParams['filter_search_phone'] = $searchLike;
        }

        $countSql = 'SELECT COUNT(*) FROM users u WHERE ' . implode(' AND ', $whereParts);
        $total = $this->fetchCount($countSql, $whereParams);

        $postParams = [];
        $postWhere = $this->getPostWindowCondition('p', $window);
        $postWhere .= $this->buildDateCondition('DATE(p.created_at)', $from, $to, 'post_list', $postParams);

        $questionParams = [];
        $questionWhere = $this->getQuestionWindowCondition('q', $window);
        $questionWhere .= $this->buildDateCondition('DATE(q.added_time)', $from, $to, 'question_list', $questionParams);

        $answerParams = [];
        $answerWhere = $this->getAnswerWindowCondition('a', $window);
        $answerWhere .= $this->buildDateCondition('DATE(a.added_time)', $from, $to, 'answer_list', $answerParams);

        $hasSessionsTable = $this->hasTable('sessions');
        $sessionParams = [];
        $sessionWhere = '';
        $sessionSelect = '0 AS sessions_count';
        $sessionScore = '0';
        $sessionJoin = '';

        if ($hasSessionsTable) {
            $sessionWhere = $this->getSessionWindowCondition('s', $window);
            $sessionWhere .= $this->buildDateCondition('DATE(s.date)', $from, $to, 'session_list', $sessionParams);
            $sessionSelect = 'COALESCE(session_stats.sessions_count, 0) AS sessions_count';
            $sessionScore = 'COALESCE(session_stats.sessions_count, 0)';
            $sessionJoin = "LEFT JOIN (
                SELECT s.user_id, COUNT(*) AS sessions_count
                FROM sessions s
                WHERE {$sessionWhere}
                GROUP BY s.user_id
            ) AS session_stats ON session_stats.user_id = u.id";
        }

        $pendingSentParams = [];
        $pendingSentWhere = "c.status = 'pending'";
        $pendingSentWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'pending_sent', $pendingSentParams);

        $pendingReceivedParams = [];
        $pendingReceivedWhere = "c.status = 'pending'";
        $pendingReceivedWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'pending_received', $pendingReceivedParams);

        $acceptedRequesterParams = [];
        $acceptedRequesterWhere = "c.status = 'accepted'";
        $acceptedRequesterWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'accepted_requester', $acceptedRequesterParams);

        $acceptedReceiverParams = [];
        $acceptedReceiverWhere = "c.status = 'accepted'";
        $acceptedReceiverWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'accepted_receiver', $acceptedReceiverParams);

        $notificationParams = [];
        $notificationWhere = '1 = 1';
        $notificationWhere .= $this->buildDateCondition('DATE(n.created_at)', $from, $to, 'notification_list', $notificationParams);

        $sql = "SELECT
                    u.id,
                    u.first_name,
                    u.last_name,
                    u.email,
                    u.phone,
                    u.role,
                    u.created_at,
                    u.public,
                    u.moderator,
                    COALESCE(post_stats.posts_count, 0) AS posts_count,
                    COALESCE(question_stats.questions_count, 0) AS questions_count,
                    COALESCE(answer_stats.answers_count, 0) AS answers_count,
                    {$sessionSelect},
                    COALESCE(pending_sent_stats.pending_sent_count, 0) AS pending_sent_count,
                    COALESCE(pending_received_stats.pending_received_count, 0) AS pending_received_count,
                    COALESCE(accepted_stats.accepted_connections_count, 0) AS accepted_connections_count,
                    COALESCE(notification_stats.notifications_count, 0) AS notifications_count,
                    COALESCE(notification_stats.notifications_unread_count, 0) AS notifications_unread_count,
                    (
                        COALESCE(post_stats.posts_count, 0)
                        + COALESCE(question_stats.questions_count, 0)
                        + COALESCE(answer_stats.answers_count, 0)
                        + {$sessionScore}
                        + COALESCE(pending_sent_stats.pending_sent_count, 0)
                        + COALESCE(pending_received_stats.pending_received_count, 0)
                        + COALESCE(accepted_stats.accepted_connections_count, 0)
                        + COALESCE(notification_stats.notifications_count, 0)
                    ) AS activity_score
                FROM users u
                LEFT JOIN (
                    SELECT p.user_id, COUNT(*) AS posts_count
                    FROM feed_posts p
                    WHERE {$postWhere}
                    GROUP BY p.user_id
                ) AS post_stats ON post_stats.user_id = u.id
                LEFT JOIN (
                    SELECT q.user_id, COUNT(*) AS questions_count
                    FROM questions q
                    WHERE {$questionWhere}
                    GROUP BY q.user_id
                ) AS question_stats ON question_stats.user_id = u.id
                LEFT JOIN (
                    SELECT a.user_id, COUNT(*) AS answers_count
                    FROM answers a
                    WHERE {$answerWhere}
                    GROUP BY a.user_id
                ) AS answer_stats ON answer_stats.user_id = u.id
                {$sessionJoin}
                LEFT JOIN (
                    SELECT c.requester_id AS user_id, COUNT(*) AS pending_sent_count
                    FROM connections c
                    WHERE {$pendingSentWhere}
                    GROUP BY c.requester_id
                ) AS pending_sent_stats ON pending_sent_stats.user_id = u.id
                LEFT JOIN (
                    SELECT c.receiver_id AS user_id, COUNT(*) AS pending_received_count
                    FROM connections c
                    WHERE {$pendingReceivedWhere}
                    GROUP BY c.receiver_id
                ) AS pending_received_stats ON pending_received_stats.user_id = u.id
                LEFT JOIN (
                    SELECT connection_owner.user_id, COUNT(*) AS accepted_connections_count
                    FROM (
                        SELECT c.requester_id AS user_id
                        FROM connections c
                        WHERE {$acceptedRequesterWhere}
                        UNION ALL
                        SELECT c.receiver_id AS user_id
                        FROM connections c
                        WHERE {$acceptedReceiverWhere}
                    ) AS connection_owner
                    GROUP BY connection_owner.user_id
                ) AS accepted_stats ON accepted_stats.user_id = u.id
                LEFT JOIN (
                    SELECT n.subscriber_id AS user_id,
                           COUNT(*) AS notifications_count,
                           SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) AS notifications_unread_count
                    FROM notifications n
                    WHERE {$notificationWhere}
                    GROUP BY n.subscriber_id
                ) AS notification_stats ON notification_stats.user_id = u.id
                WHERE " . implode(' AND ', $whereParts) . "
                ORDER BY activity_score DESC, u.created_at DESC
                LIMIT :list_limit OFFSET :list_offset";

        $params = array_merge(
            $whereParams,
            $postParams,
            $questionParams,
            $answerParams,
            $sessionParams,
            $pendingSentParams,
            $pendingReceivedParams,
            $acceptedRequesterParams,
            $acceptedReceiverParams,
            $notificationParams
        );

        $params['list_limit'] = $limit;
        $params['list_offset'] = $offset;

        $rows = $this->fetchAll($sql, $params, ['list_limit', 'list_offset']);

        return [
            'items' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => $total > 0 ? (int)ceil($total / $limit) : 1,
            ],
        ];
    }

    public function getUserActivityDetail(int $userId, string $window, ?string $from, ?string $to): ?array
    {
        $userSql = 'SELECT id, first_name, last_name, email, phone, role, created_at, public, moderator FROM users WHERE id = :user_id LIMIT 1';
        $user = $this->fetchOne($userSql, ['user_id' => $userId]);

        if (!$user) {
            return null;
        }

        $postParams = ['detail_post_user_id' => $userId];
        $postWhere = $this->getPostWindowCondition('p', $window) . ' AND p.user_id = :detail_post_user_id';
        $postWhere .= $this->buildDateCondition('DATE(p.created_at)', $from, $to, 'detail_post_date', $postParams);
        $postsCount = $this->fetchCount("SELECT COUNT(*) FROM feed_posts p WHERE {$postWhere}", $postParams);

        $questionParams = ['detail_question_user_id' => $userId];
        $questionWhere = $this->getQuestionWindowCondition('q', $window) . ' AND q.user_id = :detail_question_user_id';
        $questionWhere .= $this->buildDateCondition('DATE(q.added_time)', $from, $to, 'detail_question_date', $questionParams);
        $questionsCount = $this->fetchCount("SELECT COUNT(*) FROM questions q WHERE {$questionWhere}", $questionParams);

        $answerParams = ['detail_answer_user_id' => $userId];
        $answerWhere = $this->getAnswerWindowCondition('a', $window) . ' AND a.user_id = :detail_answer_user_id';
        $answerWhere .= $this->buildDateCondition('DATE(a.added_time)', $from, $to, 'detail_answer_date', $answerParams);
        $answersCount = $this->fetchCount("SELECT COUNT(*) FROM answers a WHERE {$answerWhere}", $answerParams);

        $sessionsCount = 0;
        $sessions = [];

        if ($this->hasTable('sessions')) {
            $sessionParams = ['detail_session_user_id' => $userId];
            $sessionWhere = $this->getSessionWindowCondition('s', $window) . ' AND s.user_id = :detail_session_user_id';
            $sessionExpiredCondition = $this->getSessionExpiredCondition('s');
            $sessionWhere .= $this->buildDateCondition('DATE(s.date)', $from, $to, 'detail_session_date', $sessionParams);
            $sessionsCount = $this->fetchCount("SELECT COUNT(*) FROM sessions s WHERE {$sessionWhere}", $sessionParams);

            $sessions = $this->fetchAll(
                "SELECT s.id,
                        s.title,
                        s.subject,
                        s.audience,
                        s.date,
                        s.time,
                        s.is_deleted,
                        s.deleted_at,
                        CASE
                            WHEN s.is_deleted = 1
                                 OR s.deleted_at IS NOT NULL
                                 OR {$sessionExpiredCondition}
                            THEN 'archived'
                            ELSE 'active'
                        END AS session_state
                 FROM sessions s
                 WHERE {$sessionWhere}
                 ORDER BY s.date DESC, s.time DESC
                 LIMIT 25",
                $sessionParams
            );
        }

        $pendingSentParams = ['detail_pending_sent_user_id' => $userId];
        $pendingSentWhere = "c.status = 'pending' AND c.requester_id = :detail_pending_sent_user_id";
        $pendingSentWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'detail_pending_sent_date', $pendingSentParams);
        $pendingSentCount = $this->fetchCount("SELECT COUNT(*) FROM connections c WHERE {$pendingSentWhere}", $pendingSentParams);

        $pendingReceivedParams = ['detail_pending_received_user_id' => $userId];
        $pendingReceivedWhere = "c.status = 'pending' AND c.receiver_id = :detail_pending_received_user_id";
        $pendingReceivedWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'detail_pending_received_date', $pendingReceivedParams);
        $pendingReceivedCount = $this->fetchCount("SELECT COUNT(*) FROM connections c WHERE {$pendingReceivedWhere}", $pendingReceivedParams);

        $acceptedParams = [
            'detail_accepted_requester_id' => $userId,
            'detail_accepted_receiver_id' => $userId,
        ];
        $acceptedWhere = "c.status = 'accepted'";
        $acceptedWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'detail_accepted_date', $acceptedParams);

        $acceptedCount = $this->fetchCount(
            "SELECT COUNT(*)
             FROM connections c
             WHERE ({$acceptedWhere})
               AND (c.requester_id = :detail_accepted_requester_id OR c.receiver_id = :detail_accepted_receiver_id)",
            $acceptedParams
        );

        $notificationParams = ['detail_notification_user_id' => $userId];
        $notificationWhere = 'n.subscriber_id = :detail_notification_user_id';
        $notificationWhere .= $this->buildDateCondition('DATE(n.created_at)', $from, $to, 'detail_notification_date', $notificationParams);

        $notificationCounts = $this->fetchOne(
            "SELECT COUNT(*) AS notifications_count,
                    SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) AS notifications_unread_count
             FROM notifications n
             WHERE {$notificationWhere}",
            $notificationParams
        );

        $posts = $this->fetchAll(
            "SELECT p.id, p.title, p.post_type, p.created_at, p.deleted_at, p.is_deleted
             FROM feed_posts p
             WHERE {$postWhere}
             ORDER BY p.created_at DESC
             LIMIT 25",
            $postParams
        );

        $questions = $this->fetchAll(
            "SELECT q.q_id, q.question, q.status, q.added_time, q.last_modified
             FROM questions q
             WHERE {$questionWhere}
             ORDER BY q.added_time DESC
             LIMIT 25",
            $questionParams
        );

        $answers = $this->fetchAll(
            "SELECT a.a_id, a.q_id, LEFT(a.text, 220) AS text_excerpt, a.status, a.added_time
             FROM answers a
             WHERE {$answerWhere}
             ORDER BY a.added_time DESC
             LIMIT 25",
            $answerParams
        );

        $connectionParams = [
            'detail_connection_direction_case' => $userId,
            'detail_connection_counterpart_case' => $userId,
            'detail_connection_join_case' => $userId,
            'detail_connection_user_id_requester' => $userId,
            'detail_connection_user_id_receiver' => $userId,
        ];

        $connectionWhere = '(c.requester_id = :detail_connection_user_id_requester OR c.receiver_id = :detail_connection_user_id_receiver)';
        $connectionWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'detail_connection_date', $connectionParams);

        $connections = $this->fetchAll(
            "SELECT c.requester_id,
                    c.receiver_id,
                    c.status,
                    c.created_at,
                    CASE
                        WHEN c.requester_id = :detail_connection_direction_case THEN 'sent'
                        ELSE 'received'
                    END AS direction,
                    CASE
                        WHEN c.requester_id = :detail_connection_counterpart_case THEN c.receiver_id
                        ELSE c.requester_id
                    END AS counterpart_id,
                    CONCAT(other_user.first_name, ' ', other_user.last_name) AS counterpart_name,
                    other_user.role AS counterpart_role
             FROM connections c
             LEFT JOIN users other_user
                    ON other_user.id = CASE
                        WHEN c.requester_id = :detail_connection_join_case THEN c.receiver_id
                        ELSE c.requester_id
                    END
             WHERE {$connectionWhere}
             ORDER BY c.created_at DESC
             LIMIT 25",
            $connectionParams
        );

        $notifications = $this->fetchAll(
            "SELECT n.id, n.module, n.message, n.url, n.is_read, n.created_at
             FROM notifications n
             WHERE {$notificationWhere}
             ORDER BY n.created_at DESC
             LIMIT 25",
            $notificationParams
        );

        return [
            'user' => $user,
            'metrics' => [
                'posts_count' => $postsCount,
                'questions_count' => $questionsCount,
                'answers_count' => $answersCount,
                'sessions_count' => $sessionsCount,
                'pending_sent_count' => $pendingSentCount,
                'pending_received_count' => $pendingReceivedCount,
                'accepted_connections_count' => $acceptedCount,
                'notifications_count' => (int)($notificationCounts['notifications_count'] ?? 0),
                'notifications_unread_count' => (int)($notificationCounts['notifications_unread_count'] ?? 0),
            ],
            'activity' => [
                'feed_posts' => $posts,
                'questions' => $questions,
                'answers' => $answers,
                'sessions' => $sessions,
                'connections' => $connections,
                'notifications' => $notifications,
            ],
        ];
    }

    private function getSummaryCounts(string $window, ?string $from, ?string $to): array
    {
        $userParams = [];
        $userWhere = '1 = 1';
        $userWhere .= $this->buildDateCondition('DATE(u.created_at)', $from, $to, 'summary_users', $userParams);
        $totalUsers = $this->fetchCount("SELECT COUNT(*) FROM users u WHERE {$userWhere}", $userParams);

        $postParams = [];
        $postWhere = $this->getPostWindowCondition('p', $window);
        $postWhere .= $this->buildDateCondition('DATE(p.created_at)', $from, $to, 'summary_posts', $postParams);
        $totalPosts = $this->fetchCount("SELECT COUNT(*) FROM feed_posts p WHERE {$postWhere}", $postParams);

        $questionParams = [];
        $questionWhere = $this->getQuestionWindowCondition('q', $window);
        $questionWhere .= $this->buildDateCondition('DATE(q.added_time)', $from, $to, 'summary_questions', $questionParams);
        $totalQuestions = $this->fetchCount("SELECT COUNT(*) FROM questions q WHERE {$questionWhere}", $questionParams);

        $answerParams = [];
        $answerWhere = $this->getAnswerWindowCondition('a', $window);
        $answerWhere .= $this->buildDateCondition('DATE(a.added_time)', $from, $to, 'summary_answers', $answerParams);
        $totalAnswers = $this->fetchCount("SELECT COUNT(*) FROM answers a WHERE {$answerWhere}", $answerParams);

        $totalSessions = 0;
        if ($this->hasTable('sessions')) {
            $sessionParams = [];
            $sessionWhere = $this->getSessionWindowCondition('s', $window);
            $sessionWhere .= $this->buildDateCondition('DATE(s.date)', $from, $to, 'summary_sessions', $sessionParams);
            $totalSessions = $this->fetchCount("SELECT COUNT(*) FROM sessions s WHERE {$sessionWhere}", $sessionParams);
        }

        $connectionParams = [];
        $connectionWhere = '1 = 1';
        $connectionWhere .= $this->buildDateCondition('DATE(c.created_at)', $from, $to, 'summary_connections', $connectionParams);

        $connectionCounts = $this->fetchOne(
            "SELECT
                SUM(CASE WHEN c.status = 'pending' THEN 1 ELSE 0 END) AS pending_count,
                SUM(CASE WHEN c.status = 'accepted' THEN 1 ELSE 0 END) AS accepted_count
             FROM connections c
             WHERE {$connectionWhere}",
            $connectionParams
        );

        $notificationParams = [];
        $notificationWhere = '1 = 1';
        $notificationWhere .= $this->buildDateCondition('DATE(n.created_at)', $from, $to, 'summary_notifications', $notificationParams);

        $notificationCounts = $this->fetchOne(
            "SELECT
                COUNT(*) AS total_count,
                SUM(CASE WHEN n.is_read = 0 THEN 1 ELSE 0 END) AS unread_count
             FROM notifications n
             WHERE {$notificationWhere}",
            $notificationParams
        );

        return [
            'total_users' => $totalUsers,
            'total_posts' => $totalPosts,
            'total_questions' => $totalQuestions,
            'total_answers' => $totalAnswers,
            'total_sessions' => $totalSessions,
            'pending_connections' => (int)($connectionCounts['pending_count'] ?? 0),
            'accepted_connections' => (int)($connectionCounts['accepted_count'] ?? 0),
            'total_notifications' => (int)($notificationCounts['total_count'] ?? 0),
            'unread_notifications' => (int)($notificationCounts['unread_count'] ?? 0),
        ];
    }

    private function getRoleDistribution(?string $from, ?string $to): array
    {
        $params = [];
        $where = '1 = 1';
        $where .= $this->buildDateCondition('DATE(u.created_at)', $from, $to, 'role_distribution', $params);

        $sql = "SELECT u.role, COUNT(*) AS role_count
                FROM users u
                WHERE {$where}
                GROUP BY u.role
                ORDER BY role_count DESC";

        $rows = $this->fetchAll($sql, $params);
        $distribution = [];

        foreach ($rows as $row) {
            $distribution[] = [
                'role' => (string)$row['role'],
                'count' => (int)$row['role_count'],
            ];
        }

        return $distribution;
    }

    private function getActivityDistributionFromSummary(array $summary): array
    {
        return [
            ['label' => 'Feed Posts', 'count' => (int)($summary['total_posts'] ?? 0)],
            ['label' => 'Questions', 'count' => (int)($summary['total_questions'] ?? 0)],
            ['label' => 'Answers', 'count' => (int)($summary['total_answers'] ?? 0)],
            ['label' => 'Sessions', 'count' => (int)($summary['total_sessions'] ?? 0)],
            ['label' => 'Connections', 'count' => (int)($summary['accepted_connections'] ?? 0) + (int)($summary['pending_connections'] ?? 0)],
            ['label' => 'Notification Subscribers', 'count' => (int)($summary['total_notifications'] ?? 0)],
        ];
    }

    private function getPostWindowCondition(string $alias, string $window): string
    {
        if ($window === 'archived') {
            return "({$alias}.is_deleted = 1 OR {$alias}.deleted_at IS NOT NULL)";
        }

        return "({$alias}.is_deleted = 0 AND {$alias}.deleted_at IS NULL)";
    }

    private function getQuestionWindowCondition(string $alias, string $window): string
    {
        if ($window === 'archived') {
            return "COALESCE({$alias}.status, 'normal') IN ('deleted', 'removed', 'banned')";
        }

        return "COALESCE({$alias}.status, 'normal') NOT IN ('deleted', 'removed', 'banned')";
    }

    private function getAnswerWindowCondition(string $alias, string $window): string
    {
        if ($window === 'archived') {
            return "COALESCE({$alias}.status, 'normal') IN ('deleted', 'removed', 'banned')";
        }

        return "COALESCE({$alias}.status, 'normal') NOT IN ('deleted', 'removed', 'banned')";
    }

    private function getSessionWindowCondition(string $alias, string $window): string
    {
        if ($window === 'archived') {
            return "({$alias}.is_deleted = 1 OR {$alias}.deleted_at IS NOT NULL OR {$this->getSessionExpiredCondition($alias)})";
        }

        return "({$alias}.is_deleted = 0 AND {$alias}.deleted_at IS NULL AND {$this->getSessionActiveCondition($alias)})";
    }

    private function getSessionExpiredCondition(string $alias): string
    {
        return 'NOW() > ' . $this->getSessionEndDateTimeExpression($alias);
    }

    private function getSessionActiveCondition(string $alias): string
    {
        return 'NOW() <= ' . $this->getSessionEndDateTimeExpression($alias);
    }

    private function getSessionEndDateTimeExpression(string $alias): string
    {
        $defaultDuration = (float)self::DEFAULT_DURATION_HOURS;
        $durationHoursExpression = "CASE
                WHEN CAST(COALESCE({$alias}.duration, 0) AS DECIMAL(10,2)) > 0
                THEN CAST({$alias}.duration AS DECIMAL(10,2))
                ELSE {$defaultDuration}
            END";

        return "DATE_ADD(
                TIMESTAMP({$alias}.date, COALESCE({$alias}.time, '00:00:00')),
                INTERVAL ({$durationHoursExpression} * 3600) SECOND
            )";
    }

    private function hasTable(string $tableName): bool
    {
        if (array_key_exists($tableName, $this->tableExistsCache)) {
            return $this->tableExistsCache[$tableName];
        }

        try {
            $count = $this->fetchCount(
                'SELECT COUNT(*) AS table_count
                 FROM information_schema.tables
                 WHERE table_schema = DATABASE()
                   AND table_name = :table_name',
                ['table_name' => $tableName]
            );

            $this->tableExistsCache[$tableName] = ($count > 0);
        } catch (Exception $e) {
            $this->tableExistsCache[$tableName] = false;
        }

        return $this->tableExistsCache[$tableName];
    }

    private function buildDateCondition(string $dateExpression, ?string $from, ?string $to, string $prefix, array &$params): string
    {
        $clauses = [];

        if ($from !== null) {
            $fromKey = $prefix . '_from';
            $clauses[] = "{$dateExpression} >= :{$fromKey}";
            $params[$fromKey] = $from;
        }

        if ($to !== null) {
            $toKey = $prefix . '_to';
            $clauses[] = "{$dateExpression} <= :{$toKey}";
            $params[$toKey] = $to;
        }

        if (empty($clauses)) {
            return '';
        }

        return ' AND ' . implode(' AND ', $clauses);
    }

    private function fetchCount(string $sql, array $params = []): int
    {
        $row = $this->fetchOne($sql, $params);

        if (!$row) {
            return 0;
        }

        $firstValue = reset($row);
        return (int)$firstValue;
    }

    private function fetchOne(string $sql, array $params = []): ?array
    {
        $rows = $this->fetchAll($sql, $params, [], true);
        if (empty($rows)) {
            return null;
        }

        return $rows[0];
    }

    private function fetchAll(string $sql, array $params = [], array $intParamKeys = [], bool $singleRow = false): array
    {
        try {
            $stmt = $this->db->prepare($sql);

            foreach ($params as $key => $value) {
                $paramName = ':' . $key;
                $isIntParam = in_array($key, $intParamKeys, true);

                if ($isIntParam || is_int($value)) {
                    $stmt->bindValue($paramName, (int)$value, PDO::PARAM_INT);
                    continue;
                }

                if ($value === null) {
                    $stmt->bindValue($paramName, null, PDO::PARAM_NULL);
                    continue;
                }

                $stmt->bindValue($paramName, (string)$value, PDO::PARAM_STR);
            }

            $stmt->execute();

            if ($singleRow) {
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ? [$row] : [];
            }

            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception('Failed to query admin overview data: ' . $e->getMessage());
        }
    }
}
