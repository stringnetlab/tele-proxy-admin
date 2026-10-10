<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;


final class GetClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	public function handle(string $cryptId): ?Client
	{
		return $this->clientRepository->get($cryptId);
	}
}
