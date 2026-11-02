<?php declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Shared\Infrastructure\RateLimiter;


final class FakeRateLimiter extends RateLimiter
{
	public bool $limited = false;

	public int $retryAfter = 0;

	public ?string $lastKey = null;


	public function __construct()
	{
	}


	public function isRateLimited(string $key, int $maxAttempts, int $windowSeconds): bool
	{
		$this->lastKey = $key;

		return $this->limited;
	}


	public function getRetryAfter(string $key): int
	{
		return $this->retryAfter;
	}
}
