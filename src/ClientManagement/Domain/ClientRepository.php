<?php declare(strict_types=1);

namespace App\ClientManagement\Domain;


interface ClientRepository
{
	/**
	 * @return array{clients: array<int, array<string, mixed>>, total: int}
	 */
	public function list(int $limit = 50, int $skip = 0): array;


	/**
	 * @return array<string, mixed>|null
	 */
	public function get(string $id): ?array;


	/**
	 * @param array<string, mixed> $data
	 * @return array{token: string, client: array<string, mixed>}
	 */
	public function create(array $data = []): array;


	/**
	 * @param array<string, mixed> $data
	 * @return array{config_version: int, updated_at: string}
	 */
	public function update(string $id, array $data): array;


	public function delete(string $id): void;


	/**
	 * @return array{token: string, client: array<string, mixed>}
	 */
	public function rotateToken(string $id): array;


	/**
	 * @return array{new_crypt_id: string, rotated_at: string}
	 */
	public function rotateCryptId(string $id): array;
}
