<?php declare(strict_types=1);

namespace App\Authentication\Infrastructure;

use App\Shared\Infrastructure\ValkeyConnection;


class ValkeySingleSessionStore
{
	private const SESSION_TTL = 900;


	public function __construct(
		private readonly ValkeyConnection $valkey,
	)
	{
	}


	public function storeSession(string $email, string $sessionId, string $ip, string $userAgent): void
	{
		$data = json_encode([
			'session_id' => $sessionId,
			'ip' => $ip,
			'user_agent' => $userAgent,
			'created_at' => gmdate('c'),
		]);

		$this->valkey->set('session:' . $email, $data, self::SESSION_TTL);
	}


	public function getSession(string $email): ?array
	{
		$data = $this->valkey->get('session:' . $email);
		if ($data === null) {
			return null;
		}

		return json_decode($data, true);
	}


	public function invalidateSession(string $email): void
	{
		$this->valkey->del('session:' . $email);
	}


	public function isValidSession(string $email, string $sessionId): bool
	{
		$session = $this->getSession($email);
		if ($session === null) {
			return false;
		}

		return ($session['session_id'] ?? '') === $sessionId;
	}
}
