<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Authentication\Infrastructure\CouchDbSessionRepository;


final class LogoutHandler
{
	public function __construct(
		private readonly ApiAuthAdapter $authAdapter,
		private readonly CouchDbSessionRepository $sessionRepository,
		private readonly EnforceSingleSessionHandler $singleSession,
	) {}


	public function handle(string $email, string $netteSessionId, ?string $apiCookie): void
	{
		if ($apiCookie !== null && $apiCookie !== '') {
			try {
				$this->authAdapter->logout($apiCookie);
			} catch (\Throwable) {
				// El logout del API es best-effort: la sesión local se cierra igualmente.
			}
		}

		$this->singleSession->invalidate($email);
		$this->sessionRepository->deleteSession($netteSessionId);
		$this->sessionRepository->logAuditEvent('admin_ui_logout', $email);
	}
}
