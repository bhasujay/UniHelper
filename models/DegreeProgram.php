<?php

namespace app\models;

use app\core\Database;

class DegreeProgram {
    
    private $db;
    private $pdo;
    private $hasRequirementTables = null;
    private $degreeColumns = null;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }
    
    // Method to add a degree
    public function addDegree($data) {
        $payload = $this->normalizeDegreePayload($data);

        $columns = [
            'name' => $payload['name'],
            'university_id' => $payload['university_id'],
            'stream' => $payload['stream'],
            'unicode' => $payload['unicode'],
            'major_id' => $payload['major_id']
        ];

        if ($this->degreeColumnExists('descriptions')) {
            $columns['descriptions'] = $payload['descriptions'];
        }

        if ($this->degreeColumnExists('duration')) {
            $columns['duration'] = $payload['duration'];
        }

        $columnNames = implode(', ', array_keys($columns));
        $placeholders = ':' . implode(', :', array_keys($columns));
        $query = "INSERT INTO degree_program ({$columnNames}) VALUES ({$placeholders})";

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->db->prepare($query);
            $stmt->execute($columns);

            $programId = (int)$this->db->lastInsertId();

            $this->syncSubjectRequirements(
                $programId,
                $payload['path_description'],
                $payload['subject_requirements']
            );

            $this->pdo->commit();
            return $programId;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('DegreeProgram addDegree error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Method to delete a degree
    public function deleteDegree($id) {
        $query = "DELETE FROM degree_program WHERE program_id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        
        return $stmt->execute();
    }
    
    // Method to update a degree
    public function updateDegree($id, $data) {
        $payload = $this->normalizeDegreePayload($data);

        $columns = [
            'name' => $payload['name'],
            'university_id' => $payload['university_id'],
            'stream' => $payload['stream'],
            'unicode' => $payload['unicode'],
            'major_id' => $payload['major_id']
        ];

        if ($this->degreeColumnExists('descriptions')) {
            $columns['descriptions'] = $payload['descriptions'];
        }

        if ($this->degreeColumnExists('duration')) {
            $columns['duration'] = $payload['duration'];
        }

        $setClauses = [];
        foreach (array_keys($columns) as $columnName) {
            $setClauses[] = "{$columnName} = :{$columnName}";
        }

        $query = "UPDATE degree_program SET " . implode(', ', $setClauses) . " WHERE program_id = :id";

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->db->prepare($query);
            $columns['id'] = (int)$id;
            $stmt->execute($columns);

            $this->syncSubjectRequirements(
                (int)$id,
                $payload['path_description'],
                $payload['subject_requirements']
            );

            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            error_log('DegreeProgram updateDegree error: ' . $e->getMessage());
            return false;
        }
    }
    
    // Method to get a degree by id
    public function getDegreeById($id) {
        $query = "SELECT * FROM degree_program WHERE program_id = :id";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();

        $degree = $stmt->fetch();
        if (!$degree) {
            return null;
        }

        if (!array_key_exists('descriptions', $degree)) {
            $degree['descriptions'] = '';
        }

        if (!array_key_exists('duration', $degree)) {
            $degree['duration'] = '';
        }

        $paths = $this->fetchSubjectRequirementPaths((int)$id);
        $requirements = $this->flattenRequirementsFromPaths($paths);

        if (empty($requirements)) {
            $requirements = $this->fetchSubjectRequirements((int)$id);
        }

        $degree['path_description'] = !empty($paths) && !empty($paths[0]['path_description'])
            ? (string)$paths[0]['path_description']
            : $this->fetchPathDescription((int)$id);
        $degree['subject_requirement_paths'] = $paths;
        $degree['subject_requirements'] = $requirements;
        $degree['subject_requirements_text'] = !empty($paths)
            ? $this->formatRequirementsTextByPaths($paths)
            : $this->formatRequirementsText($requirements);

        return $degree;
    }
    
    // Method to get all degrees
    public function getAllDegrees() {
        $selectColumns = [
            'd.program_id AS id',
            'd.name',
            'd.stream',
            'd.unicode',
            'u.name AS university',
            'm.name AS major'
        ];

        if ($this->degreeColumnExists('descriptions')) {
            $selectColumns[] = 'd.descriptions';
        }

        if ($this->degreeColumnExists('duration')) {
            $selectColumns[] = 'd.duration';
        }

        $query = "SELECT " . implode(', ', $selectColumns) . "
                  FROM degree_program d
                  LEFT JOIN universities u ON d.university_id = u.id
                  LEFT JOIN majors m ON d.major_id = m.id
                  ORDER BY d.name ASC";
        
        $result = $this->db->query($query);
        $degrees = [];
        
        while($row = $result->fetch()) {
            // Convert array to object
            $degree = new \stdClass();
            $degree->id = $row['id'];
            $degree->name = $row['name'];
            $degree->stream = $row['stream'];
            $degree->unicode = $row['unicode'];
            $degree->description = $row['descriptions'] ?? '';
            $degree->duration = $row['duration'] ?? '';
            $degree->university = $row['university'];
            $degree->major = $row['major'];
            $degree->status = 'Active'; // Default status since it's not in your table

            $degree->path_description = $this->fetchPathDescription((int)$row['id']);
            $degree->subject_requirements = $this->fetchSubjectRequirements((int)$row['id']);
            
            $degrees[] = $degree;
        }
        
        return $degrees;
    }

    private function normalizeDegreePayload(array $data): array
    {
        return [
            'name' => trim((string)($data['name'] ?? '')),
            'university_id' => $this->toNullableInt($data['university_id'] ?? null),
            'stream' => trim((string)($data['stream'] ?? '')),
            'unicode' => trim((string)($data['unicode'] ?? '')),
            'major_id' => $this->toNullableInt($data['major_id'] ?? null),
            'descriptions' => trim((string)($data['descriptions'] ?? $data['description'] ?? '')),
            'duration' => trim((string)($data['duration'] ?? '')),
            'path_description' => trim((string)($data['path_description'] ?? $data['pathDescription'] ?? 'Default Entry Path')),
            'subject_requirements' => (string)($data['subject_requirements'] ?? $data['subjectRequirements'] ?? '')
        ];
    }

    private function toNullableInt($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int)$value;
    }

    private function syncSubjectRequirements(int $programId, string $pathDescription, string $requirementsText): void
    {
        if (!$this->hasRequirementTables()) {
            return;
        }

        $existingPathStmt = $this->db->prepare("SELECT path_id FROM program_entry_paths WHERE program_id = :program_id");
        $existingPathStmt->execute(['program_id' => $programId]);
        $existingPathIds = $existingPathStmt->fetchAll(\PDO::FETCH_COLUMN);

        if (!empty($existingPathIds)) {
            $placeholders = implode(', ', array_fill(0, count($existingPathIds), '?'));
            $deleteSubjectsStmt = $this->db->prepare("DELETE FROM path_subjects WHERE path_id IN ({$placeholders})");
            $deleteSubjectsStmt->execute(array_values($existingPathIds));
        }

        $deletePathsStmt = $this->db->prepare("DELETE FROM program_entry_paths WHERE program_id = :program_id");
        $deletePathsStmt->execute(['program_id' => $programId]);

        $pathPayloads = $this->parseRequirementPathsText($requirementsText, $pathDescription);
        if (empty($pathPayloads)) {
            return;
        }

        $insertPathStmt = $this->db->prepare(
            "INSERT INTO program_entry_paths (program_id, description) VALUES (:program_id, :description)"
        );
        $insertSubjectStmt = $this->db->prepare(
            "INSERT INTO path_subjects (path_id, subject_name, min_grade) VALUES (:path_id, :subject_name, :min_grade)"
        );

        foreach ($pathPayloads as $pathPayload) {
            $pathLabel = trim((string)($pathPayload['path_description'] ?? ''));

            $insertPathStmt->execute([
                'program_id' => $programId,
                'description' => $pathLabel !== '' ? $pathLabel : 'Default Entry Path'
            ]);

            $pathId = (int)$this->db->lastInsertId();
            $requirements = is_array($pathPayload['subject_requirements'] ?? null)
                ? $pathPayload['subject_requirements']
                : [];

            foreach ($requirements as $requirement) {
                $insertSubjectStmt->execute([
                    'path_id' => $pathId,
                    'subject_name' => $requirement['subject_name'],
                    'min_grade' => $requirement['min_grade']
                ]);
            }
        }
    }

    private function parseRequirementsText(string $requirementsText): array
    {
        $requirements = [];
        $lines = preg_split('/\r\n|\r|\n/', trim($requirementsText));

        if ($lines === false) {
            return $requirements;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (preg_match('/^path\s*[:\-]/i', $line) === 1) {
                continue;
            }

            $parts = preg_split('/\s*\|\s*/', $line, 2);
            if (count($parts) < 2) {
                $parts = preg_split('/\s*:\s*/', $line, 2);
            }

            $subjectName = trim($parts[0] ?? '');
            if ($subjectName === '') {
                continue;
            }

            $minGrade = strtoupper(trim($parts[1] ?? 'S'));
            if ($minGrade === '') {
                $minGrade = 'S';
            }

            $requirements[] = [
                'subject_name' => $subjectName,
                'min_grade' => substr($minGrade, 0, 2)
            ];
        }

        return $requirements;
    }

    private function parseRequirementPathsText(string $requirementsText, string $defaultPathDescription = ''): array
    {
        $trimmedText = trim($requirementsText);
        if ($trimmedText === '') {
            return [];
        }

        $blocks = preg_split('/(?:\r\n|\r|\n)\s*(?:\r\n|\r|\n)+/', $trimmedText);
        if ($blocks === false || empty($blocks)) {
            return [];
        }

        $paths = [];
        $pathIndex = 1;
        $hasMultipleBlocks = count($blocks) > 1;

        foreach ($blocks as $block) {
            $block = trim($block);
            if ($block === '') {
                continue;
            }

            $lines = preg_split('/\r\n|\r|\n/', $block);
            if ($lines === false || empty($lines)) {
                continue;
            }

            $normalizedLines = [];
            foreach ($lines as $line) {
                $line = trim((string)$line);
                if ($line !== '') {
                    $normalizedLines[] = $line;
                }
            }

            if (empty($normalizedLines)) {
                continue;
            }

            $pathDescription = '';
            if (preg_match('/^path\s*[:\-]\s*(.+)$/i', $normalizedLines[0], $matches) === 1) {
                $pathDescription = trim((string)($matches[1] ?? ''));
                array_shift($normalizedLines);
            }

            $requirements = $this->parseRequirementsText(implode("\n", $normalizedLines));
            if (empty($requirements)) {
                continue;
            }

            if ($pathDescription === '') {
                if ($hasMultipleBlocks) {
                    $pathDescription = 'Alternative Path ' . $pathIndex;
                } elseif ($defaultPathDescription !== '') {
                    $pathDescription = $defaultPathDescription;
                } else {
                    $pathDescription = 'Default Entry Path';
                }
            }

            $paths[] = [
                'path_description' => $pathDescription,
                'subject_requirements' => $requirements
            ];
            $pathIndex++;
        }

        if (empty($paths)) {
            $requirements = $this->parseRequirementsText($trimmedText);
            if (empty($requirements)) {
                return [];
            }

            $paths[] = [
                'path_description' => $defaultPathDescription !== '' ? $defaultPathDescription : 'Default Entry Path',
                'subject_requirements' => $requirements
            ];
        }

        return $paths;
    }

    private function fetchSubjectRequirements(int $programId): array
    {
        if (!$this->hasRequirementTables()) {
            return [];
        }

        $query = "SELECT ps.subject_name, COALESCE(ps.min_grade, 'S') AS min_grade
                  FROM program_entry_paths pep
                  INNER JOIN path_subjects ps ON pep.path_id = ps.path_id
                  WHERE pep.program_id = :program_id
                  ORDER BY pep.path_id ASC, ps.id ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['program_id' => $programId]);
        return $stmt->fetchAll();
    }

    private function fetchSubjectRequirementPaths(int $programId): array
    {
        if (!$this->hasRequirementTables()) {
            return [];
        }

        $query = "SELECT pep.path_id,
                         COALESCE(pep.description, '') AS path_description,
                         ps.subject_name,
                         COALESCE(ps.min_grade, 'S') AS min_grade
                  FROM program_entry_paths pep
                  LEFT JOIN path_subjects ps ON pep.path_id = ps.path_id
                  WHERE pep.program_id = :program_id
                  ORDER BY pep.path_id ASC, ps.id ASC";

        $stmt = $this->db->prepare($query);
        $stmt->execute(['program_id' => $programId]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return [];
        }

        $pathsById = [];
        foreach ($rows as $row) {
            $pathId = (int)($row['path_id'] ?? 0);
            if ($pathId <= 0) {
                continue;
            }

            if (!isset($pathsById[$pathId])) {
                $pathsById[$pathId] = [
                    'path_id' => $pathId,
                    'path_description' => (string)($row['path_description'] ?? ''),
                    'subject_requirements' => []
                ];
            }

            $subjectName = trim((string)($row['subject_name'] ?? ''));
            if ($subjectName === '') {
                continue;
            }

            $pathsById[$pathId]['subject_requirements'][] = [
                'subject_name' => $subjectName,
                'min_grade' => (string)($row['min_grade'] ?? 'S')
            ];
        }

        return array_values($pathsById);
    }

    private function flattenRequirementsFromPaths(array $paths): array
    {
        if (empty($paths)) {
            return [];
        }

        $requirements = [];
        foreach ($paths as $path) {
            if (!isset($path['subject_requirements']) || !is_array($path['subject_requirements'])) {
                continue;
            }

            foreach ($path['subject_requirements'] as $requirement) {
                $subjectName = trim((string)($requirement['subject_name'] ?? ''));
                if ($subjectName === '') {
                    continue;
                }

                $requirements[] = [
                    'subject_name' => $subjectName,
                    'min_grade' => (string)($requirement['min_grade'] ?? 'S')
                ];
            }
        }

        return $requirements;
    }

    private function fetchPathDescription(int $programId): string
    {
        if (!$this->hasRequirementTables()) {
            return '';
        }

        $stmt = $this->db->prepare(
            "SELECT description FROM program_entry_paths WHERE program_id = :program_id ORDER BY path_id ASC LIMIT 1"
        );
        $stmt->execute(['program_id' => $programId]);

        $pathDescription = $stmt->fetchColumn();
        return $pathDescription !== false ? (string)$pathDescription : '';
    }

    private function formatRequirementsText(array $requirements): string
    {
        if (empty($requirements)) {
            return '';
        }

        $lines = [];
        foreach ($requirements as $requirement) {
            $subject = (string)($requirement['subject_name'] ?? '');
            $grade = (string)($requirement['min_grade'] ?? 'S');
            if ($subject === '') {
                continue;
            }

            $lines[] = $subject . '|' . ($grade !== '' ? $grade : 'S');
        }

        return implode("\n", $lines);
    }

    private function formatRequirementsTextByPaths(array $paths): string
    {
        if (empty($paths)) {
            return '';
        }

        $blocks = [];
        $includePathLabels = count($paths) > 1;
        $pathIndex = 1;

        foreach ($paths as $path) {
            $requirements = is_array($path['subject_requirements'] ?? null)
                ? $path['subject_requirements']
                : [];

            if (empty($requirements)) {
                continue;
            }

            $lines = [];
            if ($includePathLabels) {
                $pathDescription = trim((string)($path['path_description'] ?? ''));
                if ($pathDescription === '') {
                    $pathDescription = 'Path ' . $pathIndex;
                }
                $lines[] = 'Path: ' . $pathDescription;
            }

            foreach ($requirements as $requirement) {
                $subject = (string)($requirement['subject_name'] ?? '');
                $grade = (string)($requirement['min_grade'] ?? 'S');
                if ($subject === '') {
                    continue;
                }

                $lines[] = $subject . '|' . ($grade !== '' ? $grade : 'S');
            }

            if (!empty($lines)) {
                $blocks[] = implode("\n", $lines);
                $pathIndex++;
            }
        }

        return implode("\n\n", $blocks);
    }

    private function hasRequirementTables(): bool
    {
        if ($this->hasRequirementTables !== null) {
            return $this->hasRequirementTables;
        }

        $this->hasRequirementTables = $this->tableExists('program_entry_paths') && $this->tableExists('path_subjects');
        return $this->hasRequirementTables;
    }

    private function degreeColumnExists(string $columnName): bool
    {
        if ($this->degreeColumns === null) {
            $this->degreeColumns = [];
            $stmt = $this->db->query('SHOW COLUMNS FROM degree_program');
            while ($row = $stmt->fetch()) {
                if (isset($row['Field'])) {
                    $this->degreeColumns[$row['Field']] = true;
                }
            }
        }

        return isset($this->degreeColumns[$columnName]);
    }

    private function tableExists(string $tableName): bool
    {
        $query = "SELECT COUNT(*) FROM information_schema.TABLES
                  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table_name";
        $stmt = $this->db->prepare($query);
        $stmt->execute(['table_name' => $tableName]);
        return (int)$stmt->fetchColumn() > 0;
    }
}
?>