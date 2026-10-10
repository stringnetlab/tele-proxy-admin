<?php declare(strict_types=1);

namespace App\Authentication\Infrastructure;

use App\Shared\Infrastructure\CouchDbConnection;


class CouchDbSessionRepository
{
	public function __construct(
		private readonly CouchDbConnection $connection,
	)
	{
	}


	public function saveSession(string $sessionId, string $email, string $role, string $ip, string $userAgent): bool
	{
		return $this->connection->put('session_' . $sessionId, [
			'type' => 'session',
			'session_id' => $sessionId,
			'email' => $email,
			'role' => $role,
			'ip' => $ip,
			'user_agent' => $userAgent,
			'created_at' => gmdate('c'),
		]);
	}


	public function getSession(string $sessionId): ?array
	{
		return $this->connection->get('session_' . $sessionId);
	}


	public function deleteSession(string $sessionId): bool
	{
		$doc = $this->getSession($sessionId);
		if ($doc === null) {
			return false;
		}

		return $this->connection->delete('session_' . $sessionId, $doc['_rev'] ?? '');
	}


	public function logAuditEvent(string $event, string $email, array $data = []): bool
	{
		return $this->connection->logEvent($event, $email, $data);
	}
}
