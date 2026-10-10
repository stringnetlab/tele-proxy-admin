<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Infrastructure\ValkeySingleSessionStore;


final class EnforceSingleSessionHandler
{
	public function __construct(
		private readonly ValkeySingleSessionStore $singleSessionStore,
	) {}


	public function isValid(string $email, string $sessionId): bool
	{
		return $this->singleSessionStore->isValidSession($email, $sessionId);
	}
}
