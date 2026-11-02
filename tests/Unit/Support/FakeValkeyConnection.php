<?php declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Shared\Infrastructure\ValkeyConnection;


final class FakeValkeyConnection extends ValkeyConnection
{
	/** @var array<string, string> */
	public array $data = [];

	/** @var array<string, int> */
	public array $ttls = [];


	public function __construct()
	{
	}


	public function get(string $key): ?string
	{
		return $this->data[$key] ?? null;
	}


	public function set(string $key, string $value, int $ttlSeconds = 0): bool
	{
		$this->data[$key] = $value;
		$this->ttls[$key] = $ttlSeconds;

		return true;
	}


	public function del(string $key): bool
	{
		$existed = array_key_exists($key, $this->data);
		unset($this->data[$key]);

		return $existed;
	}


	public function getDel(string $key): ?string
	{
		if (!array_key_exists($key, $this->data)) {
			return null;
		}

		$value = $this->data[$key];
		unset($this->data[$key]);

		return $value;
	}
}
