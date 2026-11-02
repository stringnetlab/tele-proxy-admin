<?php declare(strict_types=1);

namespace App\Authentication\Domain;


final class Session
{
	public function __construct(
		public readonly string $netteSessionId,
		public readonly string $email,
		public readonly string $role,
		public readonly string $apiCookie,
		public readonly int $expiresAt,
		public readonly string $ip,
		public readonly string $userAgent,
	)
	{
	}


	public function isExpired(int $now): bool
	{
		return $this->expiresAt <= $now;
	}


	/**
	 * Metadatos persistibles. El cookie del API nunca sale de este objeto.
	 *
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'nette_session_id' => $this->netteSessionId,
			'email' => $this->email,
			'role' => $this->role,
			'ip' => $this->ip,
			'user_agent' => $this->userAgent,
			'expires_at' => gmdate('c', $this->expiresAt),
		];
	}
}
