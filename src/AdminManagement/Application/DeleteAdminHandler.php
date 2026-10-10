<?php declare(strict_types=1);

namespace App\AdminManagement\Application;

use App\AdminManagement\Domain\AdminRepository;


final class DeleteAdminHandler
{
	public function __construct(
		private readonly AdminRepository $adminRepository,
	) {}


	public function handle(string $email): void
	{
		$this->adminRepository->delete($email);
	}
}
