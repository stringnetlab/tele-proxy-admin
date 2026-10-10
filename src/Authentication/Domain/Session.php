<?php declare(strict_types=1);

namespace App\Authentication\Domain;


final class Session
{
	public function __construct(
		public readonly string $sessionId,
		public readonly string $email,
		public readonly string $role,
		public readonly string $ip,
		public readonly string $userAgent,
		public readonly \DateTimeImmutable $createdAt,
	)
	{
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'session_id' => $this->sessionId,
			'email' => $this->email,
			'role' => $this->role,
			'ip' => $this->ip,
			'user_agent' => $this->userAgent,
			'created_at' => $this->createdAt->format('c'),
		];
	}
}
