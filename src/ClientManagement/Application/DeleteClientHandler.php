<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\ClientRepository;


final class DeleteClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	public function handle(string $cryptId): void
	{
		$this->clientRepository->delete($cryptId);
	}
}
