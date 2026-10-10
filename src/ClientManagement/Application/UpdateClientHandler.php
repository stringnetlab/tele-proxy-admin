<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;


final class UpdateClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	public function handle(string $cryptId, array $data, ?int $expectedVersion = null): Client
	{
		return $this->clientRepository->update($cryptId, $data, $expectedVersion);
	}
}
