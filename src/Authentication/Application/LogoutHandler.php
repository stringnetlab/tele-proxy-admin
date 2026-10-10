<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Authentication\Infrastructure\CouchDbSessionRepository;
use App\Authentication\Infrastructure\ValkeySingleSessionStore;


final class LogoutHandler
{
	public function __construct(
		private readonly ApiAuthAdapter $authAdapter,
		private readonly CouchDbSessionRepository $sessionRepository,
		private readonly ValkeySingleSessionStore $singleSessionStore,
	) {}


	public function handle(string $email, string $sessionId): void
	{
		$this->singleSessionStore->invalidateSession($email);
		$this->sessionRepository->delete($sessionId);
		$this->authAdapter->logout();
	}
}
