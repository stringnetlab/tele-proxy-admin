<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;


final class CreateClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return array{token: string, client: Client}
	 */
	public function handle(bool $wildcard): array
	{
		return $this->clientRepository->create($wildcard);
	}
}
