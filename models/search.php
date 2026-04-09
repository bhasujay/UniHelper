<?php

namespace app\models;

use app\core\Database;

require_once dirname(__DIR__) . '\models\base-model.php';

class search extends BaseModel
{
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
              AND q.status = 'normal'
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
              AND status = 'normal'
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
              AND status = 'normal'
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
              AND a.status = 'normal'
              AND q.status = 'normal'
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
    public function feed_search($query, $index)
    {
    }

    // searching for sessions
    public function session_search($query, $index)
    {
    }
}
