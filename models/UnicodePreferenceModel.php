<?php

namespace app\models;

use PDO;
use PDOException;
use Exception;

class UnicodePreferenceModel extends BaseModel {
    protected $table = 'unicode_preferences';
    protected $primaryKey = 'id';

    private static $tableInitialized = false;

    public function __construct() {
        parent::__construct();
        $this->ensureTableExists();
    }

    /**
     * Create table automatically in local/dev environments.
     */
    private function ensureTableExists() {
        if (self::$tableInitialized) {
            return;
        }

        $pdo = $this->db->getConnection();

        // Avoid CREATE attempts on environments where the app user has no DDL privileges.
        if ($this->tableExists($pdo)) {
            self::$tableInitialized = true;
            return;
        }

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table} (
                    id INT PRIMARY KEY AUTO_INCREMENT,
                    user_id INT NOT NULL,
                    program_id INT NOT NULL,
                    preference_order INT NOT NULL,
                    probability_percent DECIMAL(5,2) DEFAULT NULL,
                    eligibility_level ENUM('very_likely', 'likely') DEFAULT NULL,
                    recommendation_score DECIMAL(6,2) DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY unique_user_program (user_id, program_id),
                    UNIQUE KEY unique_user_order (user_id, preference_order),
                    KEY idx_user_order (user_id, preference_order),
                    CONSTRAINT fk_unicode_pref_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    CONSTRAINT fk_unicode_pref_program FOREIGN KEY (program_id) REFERENCES degree_program(program_id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        try {
            $pdo->exec($sql);
            self::$tableInitialized = true;
        } catch (PDOException $e) {
            // If CREATE fails due permission constraints but table already exists, continue safely.
            if ($this->tableExists($pdo)) {
                self::$tableInitialized = true;
                return;
            }

            throw new Exception('Failed to initialize unicode preferences table: ' . $e->getMessage());
        }
    }

    private function tableExists(PDO $pdo) {
        $sql = "SELECT 1
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = :table_name
                LIMIT 1";

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['table_name' => $this->table]);
            return $stmt->fetchColumn() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Returns saved preference order map: [program_id => preference_order].
     */
    public function getUserPreferenceOrderMap($userId) {
        $sql = "SELECT program_id, preference_order
                FROM {$this->table}
                WHERE user_id = :user_id
                ORDER BY preference_order ASC";

        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute(['user_id' => $userId]);

            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $map = [];

            foreach ($rows as $row) {
                $map[(int) $row['program_id']] = (int) $row['preference_order'];
            }

            return $map;
        } catch (PDOException $e) {
            throw new Exception('Failed to fetch preference order map: ' . $e->getMessage());
        }
    }

    /**
     * Replaces a user's entire preference list with the provided ordered records.
     */
    public function replaceUserPreferences($userId, $orderedPreferences) {
        $pdo = $this->db->getConnection();

        try {
            $pdo->beginTransaction();

            $deleteStmt = $pdo->prepare("DELETE FROM {$this->table} WHERE user_id = :user_id");
            $deleteStmt->execute(['user_id' => $userId]);

            if (!empty($orderedPreferences)) {
                $insertSql = "INSERT INTO {$this->table}
                                (user_id, program_id, preference_order, probability_percent, eligibility_level, recommendation_score)
                              VALUES
                                (:user_id, :program_id, :preference_order, :probability_percent, :eligibility_level, :recommendation_score)";
                $insertStmt = $pdo->prepare($insertSql);

                foreach ($orderedPreferences as $preference) {
                    $insertStmt->execute([
                        'user_id' => (int) $userId,
                        'program_id' => (int) $preference['program_id'],
                        'preference_order' => (int) $preference['preference_order'],
                        'probability_percent' => isset($preference['probability_percent']) ? (float) $preference['probability_percent'] : null,
                        'eligibility_level' => $preference['eligibility_level'] ?? null,
                        'recommendation_score' => isset($preference['recommendation_score']) ? (float) $preference['recommendation_score'] : null,
                    ]);
                }
            }

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw new Exception('Failed to replace user preferences: ' . $e->getMessage());
        }
    }

    /**
     * Clears saved preferences for the user.
     */
    public function clearUserPreferences($userId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = :user_id";

        try {
            $stmt = $this->db->getConnection()->prepare($sql);
            $stmt->execute(['user_id' => $userId]);
            return true;
        } catch (PDOException $e) {
            throw new Exception('Failed to clear preferences: ' . $e->getMessage());
        }
    }
}
