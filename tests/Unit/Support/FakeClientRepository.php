<?php declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;
use App\Shared\Infrastructure\ApiError;
use Throwable;


final class FakeClientRepository implements ClientRepository
{
	/** @var list<Client> */
	public array $clients = [];

	/** @var list<bool> */
	public array $createCalls = [];

	/** @var list<array{internalId: string, payload: array<string, mixed>}> */
	public array $updateCalls = [];

	/** @var list<string> */
	public array $deleteCalls = [];

	public ?ApiError $createError = null;

	public ?Throwable $updateError = null;

	public string $createdToken = 'tok-created';

	public string $createdInternalId = 'int-created';


	/**
	 * @return array{clients: list<Client>, total: int}
	 */
	public function list(int $limit = 50, int $skip = 0): array
	{
		return [
			'clients' => array_slice($this->clients, $skip, $limit),
			'total' => count($this->clients),
		];
	}


	public function get(string $internalId): ?Client
	{
		foreach ($this->clients as $client) {
			if ($client->internalId === $internalId) {
				return $client;
			}
		}

		return null;
	}


	/**
	 * @return array{token: string, client: Client}
	 */
	public function create(bool $wildcard): array
	{
		$this->createCalls[] = $wildcard;

		if ($this->createError !== null) {
			throw $this->createError;
		}

		return [
			'token' => $this->createdToken,
			'client' => self::client($this->createdInternalId, 'cryptCreated'),
		];
	}


	/**
	 * @param array<string, mixed> $payload
	 */
	public function update(string $internalId, array $payload): int
	{
		$this->updateCalls[] = [
			'internalId' => $internalId,
			'payload' => $payload,
		];

		if ($this->updateError !== null) {
			throw $this->updateError;
		}

		return 1;
	}


	public function delete(string $internalId): void
	{
		$this->deleteCalls[] = $internalId;
	}


	/**
	 * @return array{token: string, client: Client}
	 */
	public function rotateToken(string $internalId): array
	{
		return ['token' => 'tok-rotated', 'client' => self::client($internalId)];
	}


	/**
	 * @return array{new_crypt_id: string, rotated_at: string}
	 */
	public function rotateCryptId(string $internalId): array
	{
		return ['new_crypt_id' => 'cryptRotated', 'rotated_at' => '2026-01-01T00:00:00Z'];
	}


	/**
	 * @return array{purged_keys: int, pattern: string, purged_at: string}
	 */
	public function purgeCache(string $internalId): array
	{
		return ['purged_keys' => 0, 'pattern' => '', 'purged_at' => '2026-01-01T00:00:00Z'];
	}


	public static function client(string $internalId, string $cryptId = 'cryptId00001'): Client
	{
		return Client::fromApiResponse([
			'internal_id' => $internalId,
			'crypt_id' => $cryptId,
			'kind' => 'standard',
			'wildcard' => false,
			'config_version' => 3,
			'whitelist' => ['example.com'],
			'rate_limit' => ['max_requests' => 10, 'window_seconds' => 30],
			'max_scripting_body_bytes' => 1234,
			'scripting_enabled' => true,
			'scripting_code' => 'return 1;',
			'scripting_code_hash' => 'sha256:abc',
			'scripting_expression' => 'true',
			'error_handling' => ['mode' => 'fallback'],
			'header_rules' => [['name' => 'X-Test']],
			'preview' => ['enabled' => true],
		]);
	}
}
