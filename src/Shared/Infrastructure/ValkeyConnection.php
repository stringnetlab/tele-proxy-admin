<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Predis\Client;


class ValkeyConnection
{
	private readonly Client $client;


	public function __construct(string $valkeyUrl)
	{
		$this->client = new Client($valkeyUrl, [
			'timeout' => 3.0,
			'read_write_timeout' => 3.0,
		]);
	}


	public function get(string $key): ?string
	{
		$result = $this->client->get($key);
		return $result === null ? null : (string) $result;
	}


	public function set(string $key, string $value, int $ttlSeconds = 0): bool
	{
		if ($ttlSeconds > 0) {
			$result = $this->client->setex($key, $ttlSeconds, $value);
		} else {
			$result = $this->client->set($key, $value);
		}

		return (string) $result === 'OK';
	}


	public function del(string $key): bool
	{
		return $this->client->del([$key]) > 0;
	}


	public function incr(string $key): int
	{
		return (int) $this->client->incr($key);
	}


	public function expire(string $key, int $seconds): bool
	{
		return (bool) $this->client->expire($key, $seconds);
	}


	public function exists(string $key): bool
	{
		return (bool) $this->client->exists([$key]);
	}


	public function getClient(): Client
	{
		return $this->client;
	}
}
