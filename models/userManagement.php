<?php

namespace app\models;

use app\core\Database;
use Exception;
use InvalidArgumentException;
use Throwable;

class UserManagement
{
	private $db;

	private const ARCHIVE_TABLES = [
		'banned_users' => 'banned_at',
		'deleted_users' => 'deleted_at',
	];

	private const SNAPSHOT_COLUMNS = [
		'id',
		'first_name',
		'last_name',
		'email',
		'phone',
		'password_hash',
		'role',
		'al_year',
		'university',
		'major',
		'profile_role',
		'profile_picture',
		'created_at',
		'public',
		'moderator',
	];

	private const BANNED_PROFILE_PICTURE = '/uploads/profilePictures/banned-user.png';
	private const DELETED_PROFILE_PICTURE = '/uploads/profilePictures/deleted-user.png';

	public function __construct()
	{
		$this->db = Database::getInstance();
	}

	public function getSummary(): array
	{
		return [
			'totalUsers' => $this->countVisibleUsers(),
			'pendingReports' => $this->countPendingReports(),
			'bannedAccounts' => $this->countArchiveRows('banned_users'),
			'deletedUsers' => $this->countArchiveRows('deleted_users'),
		];
	}

	public function getAllUsers(int $limit = 25, int $offset = 0, string $search = ''): array
	{
		[$limit, $offset] = $this->normalizePagination($limit, $offset);
		$search = $this->normalizeSearchTerm($search);

		$params = [];
		$searchSql = $this->buildNameSearchSql('u', $search, 'all_users', $params);

		$countSql = "
			SELECT COUNT(*)
			FROM users u
			LEFT JOIN banned_users bu ON bu.id = u.id
			LEFT JOIN deleted_users du ON du.id = u.id
			WHERE bu.id IS NULL
			  AND du.id IS NULL
			  {$searchSql}
		";

		$countStmt = $this->db->prepare($countSql);
		$countStmt->execute($params);
		$total = (int) $countStmt->fetchColumn();

		$sql = "
			SELECT
				u.id,
				u.first_name AS firstName,
				u.last_name AS lastName,
				u.email,
				u.phone,
				u.role,
				u.al_year AS alYear,
				u.university,
				u.major,
				u.profile_role AS profileRole,
				u.profile_picture AS profilePicture,
				u.created_at AS createdAt,
				u.public,
				u.moderator,
				COALESCE(uni.name, 'Not set') AS universityName
			FROM users u
			LEFT JOIN universities uni ON uni.id = u.university
			LEFT JOIN banned_users bu ON bu.id = u.id
			LEFT JOIN deleted_users du ON du.id = u.id
			WHERE bu.id IS NULL
			  AND du.id IS NULL
			  {$searchSql}
			ORDER BY u.created_at DESC, u.id DESC
			LIMIT {$limit} OFFSET {$offset}
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $this->buildPaginatedResult($items, $total, $limit, $offset);
	}

	public function getBannedUsers(int $limit = 25, int $offset = 0, string $search = ''): array
	{
		[$limit, $offset] = $this->normalizePagination($limit, $offset);
		$search = $this->normalizeSearchTerm($search);

		$params = [];
		$searchSql = $this->buildNameSearchSql('bu', $search, 'banned_users', $params);

		$countSql = "
			SELECT COUNT(*)
			FROM banned_users bu
			WHERE 1 = 1
			  {$searchSql}
		";

		$countStmt = $this->db->prepare($countSql);
		$countStmt->execute($params);
		$total = (int) $countStmt->fetchColumn();

		$sql = "
			SELECT
				bu.id,
				bu.first_name AS firstName,
				bu.last_name AS lastName,
				bu.email,
				bu.phone,
				bu.role,
				bu.al_year AS alYear,
				bu.university,
				bu.major,
				bu.profile_role AS profileRole,
				bu.profile_picture AS profilePicture,
				bu.created_at AS createdAt,
				bu.public,
				bu.moderator,
				bu.banned_at AS archivedAt,
				COALESCE(uni.name, 'Not set') AS universityName
			FROM banned_users bu
			LEFT JOIN universities uni ON uni.id = bu.university
			WHERE 1 = 1
			  {$searchSql}
			ORDER BY bu.banned_at DESC, bu.id DESC
			LIMIT {$limit} OFFSET {$offset}
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $this->buildPaginatedResult($items, $total, $limit, $offset);
	}

	public function getDeletedUsers(int $limit = 25, int $offset = 0, string $search = ''): array
	{
		[$limit, $offset] = $this->normalizePagination($limit, $offset);
		$search = $this->normalizeSearchTerm($search);

		$params = [];
		$searchSql = $this->buildNameSearchSql('du', $search, 'deleted_users', $params);

		$countSql = "
			SELECT COUNT(*)
			FROM deleted_users du
			WHERE 1 = 1
			  {$searchSql}
		";

		$countStmt = $this->db->prepare($countSql);
		$countStmt->execute($params);
		$total = (int) $countStmt->fetchColumn();

		$sql = "
			SELECT
				du.id,
				du.first_name AS firstName,
				du.last_name AS lastName,
				du.email,
				du.phone,
				du.role,
				du.al_year AS alYear,
				du.university,
				du.major,
				du.profile_role AS profileRole,
				du.profile_picture AS profilePicture,
				du.created_at AS createdAt,
				du.public,
				du.moderator,
				du.deleted_at AS archivedAt,
				COALESCE(uni.name, 'Not set') AS universityName
			FROM deleted_users du
			LEFT JOIN universities uni ON uni.id = du.university
			WHERE 1 = 1
			  {$searchSql}
			ORDER BY du.deleted_at DESC, du.id DESC
			LIMIT {$limit} OFFSET {$offset}
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $this->buildPaginatedResult($items, $total, $limit, $offset);
	}

	public function getPendingReports(int $limit = 25, int $offset = 0, string $search = ''): array
	{
		[$limit, $offset] = $this->normalizePagination($limit, $offset);
		$search = $this->normalizeSearchTerm($search);

		$params = [];
		$searchSql = $this->buildReportSearchSql($search, $params);

		$countSql = "
			SELECT COUNT(*)
			FROM profile_reports pr
			LEFT JOIN users reporter ON reporter.id = pr.reporter_id
			LEFT JOIN users reported ON reported.id = pr.reported_user_id
			WHERE pr.status = 'pending'
			  {$searchSql}
		";

		$countStmt = $this->db->prepare($countSql);
		$countStmt->execute($params);
		$total = (int) $countStmt->fetchColumn();

		$sql = "
			SELECT
				pr.id AS reportId,
				pr.reporter_id AS reporterUserId,
				pr.reported_user_id AS reportedUserId,
				pr.reason,
				pr.details,
				pr.status,
				pr.created_at AS createdAt,
				CASE
					WHEN reporter.id IS NULL THEN CONCAT('User #', pr.reporter_id)
					ELSE TRIM(CONCAT(reporter.first_name, ' ', reporter.last_name))
				END AS reporterName,
				COALESCE(reporter.profile_picture, '/uploads/profilePictures/default-pfp.png') AS reporterAvatar,
				CASE
					WHEN reported.id IS NULL THEN CONCAT('User #', pr.reported_user_id)
					ELSE TRIM(CONCAT(reported.first_name, ' ', reported.last_name))
				END AS reportedName,
				COALESCE(reported.profile_picture, '/uploads/profilePictures/default-pfp.png') AS reportedAvatar
			FROM profile_reports pr
			LEFT JOIN users reporter ON reporter.id = pr.reporter_id
			LEFT JOIN users reported ON reported.id = pr.reported_user_id
			WHERE pr.status = 'pending'
			  {$searchSql}
			ORDER BY pr.created_at DESC, pr.id DESC
			LIMIT {$limit} OFFSET {$offset}
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
		$items = $stmt->fetchAll(\PDO::FETCH_ASSOC);

		return $this->buildPaginatedResult($items, $total, $limit, $offset);
	}

	public function ignoreReport(int $reportId): bool
	{
		$sql = "
			UPDATE profile_reports
			SET status = 'ignored'
			WHERE id = :report_id
			  AND status = 'pending'
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute(['report_id' => $reportId]);
		return $stmt->rowCount() > 0;
	}

	public function submitProfileReport(int $reporterUserId, int $reportedUserId, string $reason, ?string $details = null): array
	{
		$allowedReasons = ['harassment', 'spam', 'inappropriate_pfp', 'fake_account', 'other'];
		$reason = strtolower(trim($reason));

		if (!in_array($reason, $allowedReasons, true)) {
			throw new InvalidArgumentException('Invalid report reason.');
		}

		if ($reporterUserId <= 0 || $reportedUserId <= 0) {
			throw new InvalidArgumentException('Invalid user id supplied for report.');
		}

		if ($reporterUserId === $reportedUserId) {
			throw new InvalidArgumentException('You cannot report your own profile.');
		}

		$details = $details !== null ? trim($details) : null;
		if ($details === '') {
			$details = null;
		}

		if ($reason === 'other' && empty($details)) {
			throw new InvalidArgumentException('Please provide details when selecting Other.');
		}

		if ($details !== null && strlen($details) > 1500) {
			throw new InvalidArgumentException('Report details are too long.');
		}

		$existsSql = "SELECT COUNT(*) FROM users WHERE id = :id LIMIT 1";
		$existsStmt = $this->db->prepare($existsSql);
		$existsStmt->execute(['id' => $reportedUserId]);
		if ((int) $existsStmt->fetchColumn() === 0) {
			throw new InvalidArgumentException('Reported user was not found.');
		}

		$dupSql = "
			SELECT id
			FROM profile_reports
			WHERE reporter_id = :reporter_id
			  AND reported_user_id = :reported_user_id
			  AND status = 'pending'
			LIMIT 1
		";
		$dupStmt = $this->db->prepare($dupSql);
		$dupStmt->execute([
			'reporter_id' => $reporterUserId,
			'reported_user_id' => $reportedUserId,
		]);

		$existingId = $dupStmt->fetchColumn();
		if ($existingId !== false) {
			return [
				'created' => false,
				'reportId' => (int) $existingId,
			];
		}

		$insertSql = "
			INSERT INTO profile_reports (reporter_id, reported_user_id, reason, details, status)
			VALUES (:reporter_id, :reported_user_id, :reason, :details, 'pending')
		";

		$insertStmt = $this->db->prepare($insertSql);
		$insertStmt->execute([
			'reporter_id' => $reporterUserId,
			'reported_user_id' => $reportedUserId,
			'reason' => $reason,
			'details' => $details,
		]);

		return [
			'created' => true,
			'reportId' => (int) $this->db->lastInsertId(),
		];
	}

	public function banUser(int $userId, ?int $reportId = null): bool
	{
		$connection = $this->db->getConnection();
		$connection->beginTransaction();

		try {
			$user = $this->getUserSnapshotForUpdate($userId);
			if ($user === null) {
				$connection->rollBack();
				return false;
			}

			$deletedSnapshot = $this->getArchiveSnapshotForUpdate('deleted_users', $userId);
			if ($deletedSnapshot !== null) {
				throw new InvalidArgumentException('Deleted users must be restored before banning.');
			}

			$bannedSnapshot = $this->getArchiveSnapshotForUpdate('banned_users', $userId);
			if ($bannedSnapshot === null) {
				$this->insertArchiveSnapshot('banned_users', $user);
			}

			$this->anonymizeBannedUser($userId);

			if ($reportId !== null) {
				$this->resolveReport($reportId);
			}

			$this->resolvePendingReportsForUser($userId);

			$connection->commit();
			return true;
		} catch (Throwable $e) {
			if ($connection->inTransaction()) {
				$connection->rollBack();
			}

			if ($e instanceof InvalidArgumentException) {
				throw $e;
			}

			throw new Exception('Failed to ban user: ' . $e->getMessage(), 0, $e);
		}
	}

	public function unbanUser(int $userId): bool
	{
		$connection = $this->db->getConnection();
		$connection->beginTransaction();

		try {
			$snapshot = $this->getArchiveSnapshotForUpdate('banned_users', $userId);
			if ($snapshot === null) {
				$connection->rollBack();
				return false;
			}

			$user = $this->getUserSnapshotForUpdate($userId);
			if ($user === null) {
				$connection->rollBack();
				return false;
			}

			$this->restoreUserFromSnapshot($snapshot, $userId);

			$deleteStmt = $this->db->prepare('DELETE FROM banned_users WHERE id = :id');
			$deleteStmt->execute(['id' => $userId]);

			$connection->commit();
			return true;
		} catch (Throwable $e) {
			if ($connection->inTransaction()) {
				$connection->rollBack();
			}
			throw new Exception('Failed to unban user: ' . $e->getMessage(), 0, $e);
		}
	}

	public function deleteUser(int $userId): bool
	{
		$connection = $this->db->getConnection();
		$connection->beginTransaction();

		try {
			$user = $this->getUserSnapshotForUpdate($userId);
			if ($user === null) {
				$connection->rollBack();
				return false;
			}

			$bannedSnapshot = $this->getArchiveSnapshotForUpdate('banned_users', $userId);
			if ($bannedSnapshot !== null) {
				throw new InvalidArgumentException('Banned users must be unbanned before deleting.');
			}

			$deletedSnapshot = $this->getArchiveSnapshotForUpdate('deleted_users', $userId);
			if ($deletedSnapshot === null) {
				$this->insertArchiveSnapshot('deleted_users', $user);
			}

			$this->anonymizeDeletedUser($userId);

			$connection->commit();
			return true;
		} catch (Throwable $e) {
			if ($connection->inTransaction()) {
				$connection->rollBack();
			}

			if ($e instanceof InvalidArgumentException) {
				throw $e;
			}

			throw new Exception('Failed to delete user: ' . $e->getMessage(), 0, $e);
		}
	}

	public function restoreDeletedUser(int $userId): bool
	{
		$connection = $this->db->getConnection();
		$connection->beginTransaction();

		try {
			$snapshot = $this->getArchiveSnapshotForUpdate('deleted_users', $userId);
			if ($snapshot === null) {
				$connection->rollBack();
				return false;
			}

			$user = $this->getUserSnapshotForUpdate($userId);
			if ($user === null) {
				$connection->rollBack();
				return false;
			}

			$this->restoreUserFromSnapshot($snapshot, $userId);

			$deleteStmt = $this->db->prepare('DELETE FROM deleted_users WHERE id = :id');
			$deleteStmt->execute(['id' => $userId]);

			$connection->commit();
			return true;
		} catch (Throwable $e) {
			if ($connection->inTransaction()) {
				$connection->rollBack();
			}
			throw new Exception('Failed to restore deleted user: ' . $e->getMessage(), 0, $e);
		}
	}

	public function restoreDeletedUserByEmail(string $email): bool
	{
		$email = trim($email);
		if ($email === '') {
			return false;
		}

		$connection = $this->db->getConnection();
		$connection->beginTransaction();

		try {
			$snapshotStmt = $this->db->prepare('SELECT * FROM deleted_users WHERE email = :email LIMIT 1 FOR UPDATE');
			$snapshotStmt->execute(['email' => $email]);
			$snapshot = $snapshotStmt->fetch(\PDO::FETCH_ASSOC);

			if (!$snapshot) {
				$connection->rollBack();
				return false;
			}

			$userStmt = $this->db->prepare('SELECT id FROM users WHERE email = :email LIMIT 1 FOR UPDATE');
			$userStmt->execute(['email' => $email]);
			$targetUserId = $userStmt->fetchColumn();

			if ($targetUserId === false && isset($snapshot['id'])) {
				$userById = $this->getUserSnapshotForUpdate((int) $snapshot['id']);
				if ($userById !== null) {
					$targetUserId = (int) $snapshot['id'];
				}
			}

			if ($targetUserId === false || $targetUserId === null) {
				$connection->rollBack();
				return false;
			}

			$this->restoreUserFromSnapshot($snapshot, (int) $targetUserId);

			$deleteStmt = $this->db->prepare('DELETE FROM deleted_users WHERE id = :id');
			$deleteStmt->execute(['id' => (int) $snapshot['id']]);

			$connection->commit();
			return true;
		} catch (Throwable $e) {
			if ($connection->inTransaction()) {
				$connection->rollBack();
			}

			throw new Exception('Failed to restore deleted user by email: ' . $e->getMessage(), 0, $e);
		}
	}

	private function countVisibleUsers(): int
	{
		$sql = "
			SELECT COUNT(*)
			FROM users u
			LEFT JOIN banned_users bu ON bu.id = u.id
			LEFT JOIN deleted_users du ON du.id = u.id
			WHERE bu.id IS NULL
			  AND du.id IS NULL
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	private function countPendingReports(): int
	{
		$stmt = $this->db->prepare("SELECT COUNT(*) FROM profile_reports WHERE status = 'pending'");
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	private function countArchiveRows(string $tableName): int
	{
		$this->assertArchiveTable($tableName);

		$stmt = $this->db->prepare("SELECT COUNT(*) FROM {$tableName}");
		$stmt->execute();
		return (int) $stmt->fetchColumn();
	}

	private function normalizePagination(int $limit, int $offset): array
	{
		$limit = max(1, min(100, (int) $limit));
		$offset = max(0, (int) $offset);
		return [$limit, $offset];
	}

	private function normalizeSearchTerm(string $search): string
	{
		$search = trim($search);
		if ($search === '') {
			return '';
		}

		if (strlen($search) > 120) {
			$search = substr($search, 0, 120);
		}

		return $search;
	}

	private function buildNameSearchSql(string $alias, string $search, string $paramPrefix, array &$params): string
	{
		if ($search === '') {
			return '';
		}

		$key = $paramPrefix . '_search';
		$params[$key] = '%' . $search . '%';

		return "
			AND CONCAT_WS(' ',
				COALESCE({$alias}.first_name, ''),
				COALESCE({$alias}.last_name, ''),
				COALESCE({$alias}.email, '')
			) LIKE :{$key}
		";
	}

	private function buildReportSearchSql(string $search, array &$params): string
	{
		if ($search === '') {
			return '';
		}

		$params['reports_search'] = '%' . $search . '%';

		return "
			AND CONCAT_WS(' ',
				COALESCE(reporter.first_name, ''),
				COALESCE(reporter.last_name, ''),
				COALESCE(reported.first_name, ''),
				COALESCE(reported.last_name, ''),
				COALESCE(pr.reason, ''),
				COALESCE(pr.details, '')
			) LIKE :reports_search
		";
	}

	private function buildPaginatedResult(array $items, int $total, int $limit, int $offset): array
	{
		$nextOffset = $offset + count($items);

		return [
			'items' => $items,
			'total' => $total,
			'limit' => $limit,
			'offset' => $offset,
			'nextOffset' => $nextOffset,
			'hasMore' => $nextOffset < $total,
		];
	}

	private function resolveReport(int $reportId): void
	{
		$stmt = $this->db->prepare("UPDATE profile_reports SET status = 'resolved' WHERE id = :id AND status = 'pending'");
		$stmt->execute(['id' => $reportId]);
	}

	private function resolvePendingReportsForUser(int $userId): void
	{
		$stmt = $this->db->prepare("UPDATE profile_reports SET status = 'resolved' WHERE reported_user_id = :user_id AND status = 'pending'");
		$stmt->execute(['user_id' => $userId]);
	}

	private function getUserSnapshotForUpdate(int $userId): ?array
	{
		$stmt = $this->db->prepare('SELECT * FROM users WHERE id = :id LIMIT 1 FOR UPDATE');
		$stmt->execute(['id' => $userId]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	private function getArchiveSnapshotForUpdate(string $tableName, int $userId): ?array
	{
		$this->assertArchiveTable($tableName);

		$stmt = $this->db->prepare("SELECT * FROM {$tableName} WHERE id = :id LIMIT 1 FOR UPDATE");
		$stmt->execute(['id' => $userId]);
		$row = $stmt->fetch(\PDO::FETCH_ASSOC);
		return $row ?: null;
	}

	private function insertArchiveSnapshot(string $tableName, array $user): void
	{
		$this->assertArchiveTable($tableName);
		$timestampColumn = self::ARCHIVE_TABLES[$tableName];

		$columns = array_merge(self::SNAPSHOT_COLUMNS, [$timestampColumn]);
		$columnList = implode(', ', $columns);

		$snapshotPlaceholderList = ':' . implode(', :', self::SNAPSHOT_COLUMNS);
		$valueList = $snapshotPlaceholderList . ', NOW()';

		$updates = [];
		foreach (self::SNAPSHOT_COLUMNS as $column) {
			if ($column === 'id') {
				continue;
			}
			$updates[] = "{$column} = VALUES({$column})";
		}
		$updates[] = "{$timestampColumn} = NOW()";

		$sql = "
			INSERT INTO {$tableName} ({$columnList})
			VALUES ({$valueList})
			ON DUPLICATE KEY UPDATE " . implode(', ', $updates);

		$params = [];
		foreach (self::SNAPSHOT_COLUMNS as $column) {
			$params[$column] = $user[$column] ?? null;
		}

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
	}

	private function anonymizeBannedUser(int $userId): void
	{
		$sql = "
			UPDATE users
			SET first_name = 'Banned',
				last_name = 'User',
				profile_picture = :profile_picture,
				password_hash = :password_hash,
				public = 0,
				moderator = 0
			WHERE id = :id
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			'profile_picture' => self::BANNED_PROFILE_PICTURE,
			'password_hash' => $this->generateBannedPasswordHash($userId),
			'id' => $userId,
		]);
	}

	private function anonymizeDeletedUser(int $userId): void
	{
		$sql = "
			UPDATE users
			SET first_name = 'Deleted',
				last_name = 'User',
				profile_picture = :profile_picture,
				phone = :phone
			WHERE id = :id
		";

		$stmt = $this->db->prepare($sql);
		$stmt->execute([
			'profile_picture' => self::DELETED_PROFILE_PICTURE,
			'phone' => '0000000000',
			'id' => $userId,
		]);
	}

	private function restoreUserFromSnapshot(array $snapshot, int $userId): void
	{
		$sql = "
			UPDATE users
			SET first_name = :first_name,
				last_name = :last_name,
				email = :email,
				phone = :phone,
				password_hash = :password_hash,
				role = :role,
				al_year = :al_year,
				university = :university,
				major = :major,
				profile_role = :profile_role,
				profile_picture = :profile_picture,
				created_at = :created_at,
				public = :public,
				moderator = :moderator
			WHERE id = :id
		";

		$params = [
			'id' => $userId,
			'first_name' => $snapshot['first_name'] ?? null,
			'last_name' => $snapshot['last_name'] ?? null,
			'email' => $snapshot['email'] ?? null,
			'phone' => $snapshot['phone'] ?? null,
			'password_hash' => $snapshot['password_hash'] ?? null,
			'role' => $snapshot['role'] ?? null,
			'al_year' => $snapshot['al_year'] ?? null,
			'university' => $snapshot['university'] ?? null,
			'major' => $snapshot['major'] ?? null,
			'profile_role' => $snapshot['profile_role'] ?? null,
			'profile_picture' => $snapshot['profile_picture'] ?? null,
			'created_at' => $snapshot['created_at'] ?? null,
			'public' => $snapshot['public'] ?? 1,
			'moderator' => $snapshot['moderator'] ?? 0,
		];

		$stmt = $this->db->prepare($sql);
		$stmt->execute($params);
	}

	private function generateBannedPasswordHash(int $userId): string
	{
		$entropy = bin2hex(random_bytes(32));
		return hash('sha256', $entropy . '|banned|' . $userId . '|' . microtime(true));
	}

	private function assertArchiveTable(string $tableName): void
	{
		if (!array_key_exists($tableName, self::ARCHIVE_TABLES)) {
			throw new InvalidArgumentException('Invalid archive table.');
		}
	}
}
