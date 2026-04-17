<?php

namespace app\models;

require_once dirname(__DIR__, 1) . '/models/base-model.php';

use PDO;
use PDOException;
use Exception;

class FeedLike extends BaseModel
{
    protected $table = 'feed_likes';

    private const VALID_SOURCE_TYPES = ['post', 'session'];

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
                          FROM {$this->table}
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
                $deleteSql = "DELETE FROM {$this->table}
                              WHERE id = :id";
                $deleteStmt = $this->db->prepare($deleteSql);
                $deleteStmt->execute(['id' => (int)$existingId]);
                $likedByViewer = false;
            } else {
                $insertSql = "INSERT INTO {$this->table} (user_id, source_type, source_id)
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
                    FROM {$this->table}
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
            $sql = "DELETE FROM {$this->table}
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

            $key = $this->makeKey($sourceType, $sourceId);
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
            $stats[$this->makeKey($entry['source_type'], $entry['source_id'])] = [
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
                         FROM {$this->table}
                         WHERE {$whereClause}
                         GROUP BY source_type, source_id";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $countRows = $countStmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($countRows as $row) {
                $key = $this->makeKey((string)$row['source_type'], (int)$row['source_id']);
                if (isset($stats[$key])) {
                    $stats[$key]['like_count'] = (int)$row['like_count'];
                }
            }

            if ($viewerId > 0) {
                $viewerSql = "SELECT source_type, source_id
                              FROM {$this->table}
                              WHERE user_id = :viewer_id
                                AND ({$whereClause})";
                $viewerStmt = $this->db->prepare($viewerSql);
                $viewerParams = array_merge(['viewer_id' => $viewerId], $params);
                $viewerStmt->execute($viewerParams);
                $viewerRows = $viewerStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($viewerRows as $row) {
                    $key = $this->makeKey((string)$row['source_type'], (int)$row['source_id']);
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

    private function makeKey(string $sourceType, int $sourceId): string
    {
        return $sourceType . '-' . $sourceId;
    }
}
