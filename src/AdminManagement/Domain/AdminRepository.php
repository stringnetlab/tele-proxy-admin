<?php declare(strict_types=1);

namespace App\AdminManagement\Domain;


interface AdminRepository
{
	/**
	 * @return array<int, array<string, mixed>>
	 */
	public function list(): array;


	/**
	 * @return array<string, mixed>|null
	 */
	public function get(string $email): ?array;


	/**
	 * @return array<string, mixed>
	 */
	public function add(string $email): array;


	/**
	 * @return array<string, mixed>
	 */
	public function toggle(string $email, bool $active): array;


	public function delete(string $email): void;
}
