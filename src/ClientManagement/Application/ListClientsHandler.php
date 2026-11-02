<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;


final class ListClientsHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return array{clients: list<Client>, total: int}
	 */
	public function handle(int $skip = 0, int $limit = 50): array
	{
		return $this->clientRepository->list($limit, $skip);
	}
}
