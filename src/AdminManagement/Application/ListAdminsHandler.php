<?php declare(strict_types=1);

namespace App\AdminManagement\Application;

use App\AdminManagement\Domain\AdminRepository;
use App\AdminManagement\Domain\AdminUser;


final class ListAdminsHandler
{
	public function __construct(
		private readonly AdminRepository $adminRepository,
	) {}


	/**
	 * @return list<AdminUser>
	 */
	public function handle(): array
	{
		return $this->adminRepository->list();
	}
}
