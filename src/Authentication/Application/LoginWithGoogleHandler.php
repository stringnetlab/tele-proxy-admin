<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Domain\Session;
use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Authentication\Infrastructure\CouchDbSessionRepository;
use App\Authentication\Infrastructure\ValkeySingleSessionStore;


final class LoginWithGoogleHandler
{
	public function __construct(
		private readonly ApiAuthAdapter $authAdapter,
		private readonly CouchDbSessionRepository $sessionRepository,
		private readonly ValkeySingleSessionStore $singleSessionStore,
	) {}


	public function handleCallback(string $code, string $ip, string $userAgent): ?Session
	{
		$me = $this->authAdapter->getMe($code);
		if ($me === null) {
			return null;
		}

		$session = new Session(
			sessionId: bin2hex(random_bytes(16)),
			email: $me['email'],
			role: $me['role'] ?? 'admin',
			ip: $ip,
			userAgent: $userAgent,
			createdAt: time(),
		);

		$this->singleSessionStore->invalidateSession($session->email);
		$this->singleSessionStore->storeSession($session);
		$this->sessionRepository->save($session, 'google_login');

		return $session;
	}
}
