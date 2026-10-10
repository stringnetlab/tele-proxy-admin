<?php declare(strict_types=1);

namespace App\ClientManagement\Domain\ValueObjects;

use InvalidArgumentException;


final class RateLimit
{
	public function __construct(
		public readonly int $maxRequests,
		public readonly int $windowSeconds,
	)
	{
		if ($maxRequests < 1 || $maxRequests > 10000) {
			throw new InvalidArgumentException(
				'max_requests debe estar entre 1 y 10000',
			);
		}

		if ($windowSeconds < 1 || $windowSeconds > 3600) {
			throw new InvalidArgumentException(
				'window_seconds debe estar entre 1 y 3600',
			);
		}
	}


	/**
	 * @param array<string, int> $data
	 */
	public static function fromArray(array $data): self
	{
		return new self(
			maxRequests: (int) ($data['max_requests'] ?? 50),
			windowSeconds: (int) ($data['window_seconds'] ?? 60),
		);
	}


	/**
	 * @return array<string, int>
	 */
	public function toArray(): array
	{
		return [
			'max_requests' => $this->maxRequests,
			'window_seconds' => $this->windowSeconds,
		];
	}
}
