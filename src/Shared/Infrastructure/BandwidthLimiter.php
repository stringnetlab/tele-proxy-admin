<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;


class BandwidthLimiter
{
	public function __construct(
		private readonly int $limitKib = 64,
	)
	{
	}


	public function getLimitBytes(): int
	{
		return $this->limitKib * 1024;
	}


	public function throttle(string $data): string
	{
		$chunkSize = $this->getLimitBytes();
		$length = strlen($data);
		$result = '';

		for ($offset = 0; $offset < $length; $offset += $chunkSize) {
			$result .= substr($data, $offset, $chunkSize);
			if ($offset + $chunkSize < $length) {
				usleep(1_000_000);
			}
		}

		return $result;
	}


	public function shouldThrottle(): bool
	{
		return $this->limitKib > 0;
	}
}
