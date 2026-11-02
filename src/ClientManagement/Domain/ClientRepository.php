<?php declare(strict_types=1);

namespace App\ClientManagement\Domain;


interface ClientRepository
{
	/**
	 * @return array{clients: list<Client>, total: int}
	 */
	public function list(int $limit = 50, int $skip = 0): array;


	public function get(string $internalId): ?Client;


	/**
	 * @return array{token: string, client: Client}
	 */
	public function create(bool $wildcard): array;


	/**
	 * @param array<string, mixed> $payload
	 */
	public function update(string $internalId, array $payload): int;


	public function delete(string $internalId): void;


	/**
	 * @return array{token: string, client: Client}
	 */
	public function rotateToken(string $internalId): array;


	/**
	 * @return array{new_crypt_id: string, rotated_at: string}
	 */
	public function rotateCryptId(string $internalId): array;


	/**
	 * @return array{purged_keys: int, pattern: string, purged_at: string}
	 */
	public function purgeCache(string $internalId): array;
}
