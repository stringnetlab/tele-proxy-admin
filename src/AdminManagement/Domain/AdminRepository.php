<?php declare(strict_types=1);

namespace App\AdminManagement\Domain;


interface AdminRepository
{
	/**
	 * @return list<AdminUser>
	 */
	public function list(): array;


	public function get(string $email): ?AdminUser;


	public function add(string $email): AdminUser;


	public function setActive(string $email, bool $active): AdminUser;


	public function delete(string $email): void;
}
