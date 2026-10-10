<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\ClientRepository;


final class ListClientsHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return array{clients: list<array<string, mixed>>, total: int}
	 */
	public function handle(int $page = 1, int $perPage = 20): array
	{
		return $this->clientRepository->list($page, $perPage);
	}
}
