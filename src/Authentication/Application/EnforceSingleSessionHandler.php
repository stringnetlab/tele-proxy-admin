<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Infrastructure\ValkeySingleSessionStore;


final class EnforceSingleSessionHandler
{
	public function __construct(
		private readonly ValkeySingleSessionStore $singleSessionStore,
	) {}


	public function register(string $email, string $netteSessionId, int $expiresAt): void
	{
		$this->singleSessionStore->invalidateSession($email);
		$this->singleSessionStore->storeSession($email, $netteSessionId, $expiresAt);
	}


	public function isValid(string $email, string $netteSessionId): bool
	{
		return $this->singleSessionStore->isValidSession($email, $netteSessionId);
	}


	public function invalidate(string $email): void
	{
		$this->singleSessionStore->invalidateSession($email);
	}
}
