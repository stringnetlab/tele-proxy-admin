<?php declare(strict_types=1);

namespace App\ClientManagement\Infrastructure;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;
use App\Shared\Infrastructure\ApiClient;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiSession;
use InvalidArgumentException;


final class ApiClientRepository implements ClientRepository
{
	private const BASE_PATH = '/api/v1/admin/clients';

	private const MAX_LIMIT = 200;


	public function __construct(
		private readonly ApiClient $apiClient,
		private readonly ApiSession $apiSession,
	) {}


	/**
	 * @return array{clients: list<Client>, total: int}
	 */
	public function list(int $limit = 50, int $skip = 0): array
	{
		$body = $this->http()->json('GET', self::BASE_PATH, [
			'query' => [
				'limit' => min(self::MAX_LIMIT, max(1, $limit)),
				'skip' => max(0, $skip),
			],
		]);

		$clients = [];
		$raw = $body['clients'] ?? null;
		if (is_array($raw)) {
			foreach ($raw as $item) {
				if (is_array($item)) {
					$clients[] = $this->map($item);
				}
			}
		}

		return [
			'clients' => $clients,
			'total' => (int) ($body['total'] ?? count($clients)),
		];
	}


	public function get(string $internalId): ?Client
	{
		try {
			return $this->map($this->http()->json('GET', $this->path($internalId)));
		} catch (ApiError $e) {
			if ($e->status === 404) {
				return null;
			}

			throw $e;
		}
	}


	/**
	 * @return array{token: string, client: Client}
	 */
	public function create(bool $wildcard): array
	{
		$body = $this->http()->json('POST', self::BASE_PATH, [
			'json' => ['wildcard' => $wildcard],
		]);

		return [
			'token' => $this->stringField($body, 'token'),
			'client' => $this->map($this->arrayField($body, 'client')),
		];
	}


	/**
	 * @param array<string, mixed> $payload
	 */
	public function update(string $internalId, array $payload): int
	{
		$body = $this->http()->json('PUT', $this->path($internalId), ['json' => $payload]);

		return (int) ($body['config_version'] ?? 0);
	}


	public function delete(string $internalId): void
	{
		$this->http()->json('DELETE', $this->path($internalId));
	}


	/**
	 * @return array{token: string, client: Client}
	 */
	public function rotateToken(string $internalId): array
	{
		$body = $this->http()->json('POST', $this->path($internalId) . '/rotate-token');

		return [
			'token' => $this->stringField($body, 'token'),
			'client' => $this->map($this->arrayField($body, 'client')),
		];
	}


	/**
	 * @return array{new_crypt_id: string, rotated_at: string}
	 */
	public function rotateCryptId(string $internalId): array
	{
		$body = $this->http()->json('POST', $this->path($internalId) . '/rotate-id');

		return [
			'new_crypt_id' => $this->stringField($body, 'new_crypt_id'),
			'rotated_at' => $this->stringField($body, 'rotated_at'),
		];
	}


	/**
	 * @return array{purged_keys: int, pattern: string, purged_at: string}
	 */
	public function purgeCache(string $internalId): array
	{
		$body = $this->http()->json('POST', $this->path($internalId) . '/purge-cache');

		return [
			'purged_keys' => (int) ($body['purged_keys'] ?? 0),
			'pattern' => $this->stringField($body, 'pattern'),
			'purged_at' => $this->stringField($body, 'purged_at'),
		];
	}


	private function http(): ApiClient
	{
		$cookie = $this->apiSession->cookie();

		return $cookie === null || $cookie === ''
			? $this->apiClient
			: $this->apiClient->withSessionCookie($cookie);
	}


	private function path(string $internalId): string
	{
		return self::BASE_PATH . '/' . rawurlencode($internalId);
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private function map(array $data): Client
	{
		try {
			return Client::fromApiResponse($data);
		} catch (InvalidArgumentException $e) {
			throw new ApiError(
				502,
				'invalid_response',
				'El servicio de administración devolvió una configuración de cliente no válida: ' . $e->getMessage(),
			);
		}
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private function stringField(array $data, string $key): string
	{
		$value = $data[$key] ?? null;
		if (!is_string($value) || $value === '') {
			throw new ApiError(502, 'invalid_response', 'El servicio de administración devolvió una respuesta inválida.');
		}

		return $value;
	}


	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	private function arrayField(array $data, string $key): array
	{
		$value = $data[$key] ?? null;
		if (!is_array($value)) {
			throw new ApiError(502, 'invalid_response', 'El servicio de administración devolvió una respuesta inválida.');
		}

		/** @var array<string, mixed> $value */
		return $value;
	}
}
