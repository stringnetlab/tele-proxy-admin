<?php declare(strict_types=1);

namespace App\Authentication\Infrastructure;

use App\Authentication\Domain\Session;
use App\Shared\Infrastructure\CouchDbConnection;


final class CouchDbSessionRepository
{
	public function __construct(
		private readonly CouchDbConnection $connection,
	) {}


	public function saveSession(Session $session): bool
	{
		try {
			return $this->connection->put('session_' . $session->netteSessionId, array_merge(
				$session->toArray(),
				[
					'type' => 'session',
					'created_at' => gmdate('c'),
				],
			));
		} catch (\Throwable) {
			return false;
		}
	}


	public function deleteSession(string $netteSessionId): void
	{
		try {
			$docId = 'session_' . $netteSessionId;
			$doc = $this->connection->get($docId);
			if ($doc !== null) {
				$this->connection->delete($docId, (string) ($doc['_rev'] ?? ''));
			}
		} catch (\Throwable) {
			// La eliminación de metadatos es best-effort: la sesión ya fue invalidada en Valkey.
		}
	}


	/**
	 * @param array<string, mixed> $data
	 */
	public function logAuditEvent(string $event, string $email, array $data = []): void
	{
		try {
			$this->connection->logEvent($event, $email, $data);
		} catch (\Throwable) {
		}
	}
}
