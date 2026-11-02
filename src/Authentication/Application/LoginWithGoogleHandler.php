<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Authentication\Domain\AuthenticationFailedException;
use App\Authentication\Domain\GoogleCallbackResult;
use App\Authentication\Domain\Session;
use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Authentication\Infrastructure\CouchDbSessionRepository;
use Nette\Http\Session as NetteSession;


final class LoginWithGoogleHandler
{
	public function __construct(
		private readonly ApiAuthAdapter $authAdapter,
		private readonly CouchDbSessionRepository $sessionRepository,
		private readonly EnforceSingleSessionHandler $singleSession,
		private readonly NetteSession $netteSession,
	) {}


	public function handle(string $code, string $state, string $ip, string $userAgent): Session
	{
		$callback = $this->authAdapter->exchangeGoogleCallback($code, $state);
		if (!$callback->isSuccess()) {
			throw AuthenticationFailedException::fromCallback($callback);
		}

		$me = $this->authAdapter->getMe((string) $callback->sessionCookie);
		$this->assertAdminRole($me);

		$this->netteSession->start();
		$this->netteSession->regenerateId();

		$session = new Session(
			netteSessionId: $this->netteSession->getId(),
			email: self::stringField($me, 'email'),
			role: self::stringField($me, 'role'),
			apiCookie: (string) $callback->sessionCookie,
			expiresAt: $callback->expiresAt(),
			ip: $ip,
			userAgent: $userAgent,
		);

		$this->singleSession->register($session->email, $session->netteSessionId, $session->expiresAt);
		$this->sessionRepository->saveSession($session);
		$this->sessionRepository->logAuditEvent('admin_ui_login', $session->email, [
			'ip' => $session->ip,
			'provider' => 'google',
		]);

		return $session;
	}


	/**
	 * @param array<string, mixed> $me
	 */
	private function assertAdminRole(array $me): void
	{
		$role = $me['role'] ?? null;
		if (!is_string($role) || $role !== 'admin') {
			throw new AuthenticationFailedException(
				403,
				'Tu cuenta no tiene permisos de administrador.',
			);
		}
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private static function stringField(array $data, string $key): string
	{
		$value = $data[$key] ?? null;
		if (!is_string($value) || $value === '') {
			throw new AuthenticationFailedException(
				502,
				'El API devolvió una identidad incompleta.',
			);
		}

		return $value;
	}
}
