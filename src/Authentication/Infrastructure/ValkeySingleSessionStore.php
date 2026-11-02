<?php declare(strict_types=1);

namespace App\Authentication\Infrastructure;

use App\Shared\Infrastructure\ValkeyConnection;


final class ValkeySingleSessionStore
{
	private const KEY_PREFIX = 'admin_ui_session:';


	public function __construct(
		private readonly ValkeyConnection $valkey,
	) {}


	public function storeSession(string $email, string $netteSessionId, int $expiresAt): void
	{
		$ttl = max(1, $expiresAt - time());
		$this->valkey->set(self::KEY_PREFIX . $email, $netteSessionId, $ttl);
	}


	public function isValidSession(string $email, string $netteSessionId): bool
	{
		$current = $this->valkey->get(self::KEY_PREFIX . $email);
		return $current !== null && hash_equals($current, $netteSessionId);
	}


	public function invalidateSession(string $email): void
	{
		$this->valkey->del(self::KEY_PREFIX . $email);
	}
}
