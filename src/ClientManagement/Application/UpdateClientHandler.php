<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\ClientRepository;


final class UpdateClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @param array<string, mixed> $payload
	 */
	public function handle(string $internalId, array $payload): int
	{
		return $this->clientRepository->update($internalId, $payload);
	}
}
