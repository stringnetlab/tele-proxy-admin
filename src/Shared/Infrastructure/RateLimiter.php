<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;


class RateLimiter
{
	public function __construct(
		private readonly ValkeyConnection $valkey,
	)
	{
	}


	public function isRateLimited(string $key, int $maxAttempts, int $windowSeconds): bool
	{
		$redisKey = 'rate_limit:' . $key;
		$current = $this->valkey->incr($redisKey);

		if ($current === 1) {
			$this->valkey->expire($redisKey, $windowSeconds);
		}

		return $current > $maxAttempts;
	}


	public function getRemainingAttempts(string $key, int $maxAttempts): int
	{
		$redisKey = 'rate_limit:' . $key;
		$current = (int) $this->valkey->get($redisKey);
		return max(0, $maxAttempts - $current);
	}


	public function getRetryAfter(string $key): int
	{
		$redisKey = 'rate_limit:' . $key;
		$ttl = $this->valkey->getClient()->ttl($redisKey);
		return $ttl > 0 ? (int) $ttl : 0;
	}


	public function reset(string $key): void
	{
		$this->valkey->del('rate_limit:' . $key);
	}
}
