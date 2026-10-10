<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Domain\Session;
use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Authentication\Infrastructure\CouchDbSessionRepository;
use App\Authentication\Infrastructure\ValkeySingleSessionStore;


final class LoginWithMasterTokenHandler
{
	public function __construct(
		private readonly ApiAuthAdapter $authAdapter,
		private readonly CouchDbSessionRepository $sessionRepository,
		private readonly ValkeySingleSessionStore $singleSessionStore,
	) {}


	public function handle(string $masterToken, string $ip, string $userAgent): ?Session
	{
		$verified = $this->authAdapter->verifyMasterToken($masterToken);
		if ($verified === null) {
			return null;
		}

		$session = new Session(
			sessionId: bin2hex(random_bytes(16)),
			email: $verified['email'] ?? 'master@admin',
			role: 'master',
			ip: $ip,
			userAgent: $userAgent,
			createdAt: time(),
		);

		$this->singleSessionStore->invalidateSession($session->email);
		$this->singleSessionStore->storeSession($session);
		$this->sessionRepository->save($session, 'master_token_login');

		return $session;
	}
}
