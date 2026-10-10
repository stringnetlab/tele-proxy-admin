<?php declare(strict_types=1);

namespace App\AdminManagement\Application;

use App\AdminManagement\Domain\AdminRepository;
use App\AdminManagement\Domain\AdminUser;


final class AddAdminHandler
{
	public function __construct(
		private readonly AdminRepository $adminRepository,
	) {}


	public function handle(string $email, string $role): AdminUser
	{
		return $this->adminRepository->add($email, $role);
	}
}
