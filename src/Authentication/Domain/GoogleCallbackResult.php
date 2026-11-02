<?php declare(strict_types=1);

namespace App\Authentication\Domain;


final class GoogleCallbackResult
{
	public function __construct(
		public readonly int $status,
		public readonly ?string $sessionCookie,
		public readonly ?int $maxAgeSeconds,
		public readonly string $errorCode = 'api_error',
		public readonly ?int $retryAfter = null,
	)
	{
	}


	public function isSuccess(): bool
	{
		return $this->status === 302 && $this->sessionCookie !== null && $this->sessionCookie !== '';
	}


	public function expiresAt(): int
	{
		return time() + ($this->maxAgeSeconds ?? 3600);
	}
}
