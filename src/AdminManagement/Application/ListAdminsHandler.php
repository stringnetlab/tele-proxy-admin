<?php declare(strict_types=1);

namespace App\AdminManagement\Application;

use App\AdminManagement\Domain\AdminRepository;


final class ListAdminsHandler
{
	public function __construct(
		private readonly AdminRepository $adminRepository,
	) {}


	/**
	 * @return list<array<string, mixed>>
	 */
	public function handle(): array
	{
		return $this->adminRepository->list();
	}
}
