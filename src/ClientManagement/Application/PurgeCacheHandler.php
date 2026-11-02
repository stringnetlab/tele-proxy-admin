<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\ClientRepository;


final class PurgeCacheHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return array{purged_keys: int, pattern: string, purged_at: string}
	 */
	public function handle(string $internalId): array
	{
		return $this->clientRepository->purgeCache($internalId);
	}
}
