<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;
use RuntimeException;
use Throwable;


final class CloneClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return array{token: string, client: Client}
	 */
	public function handle(string $internalId): array
	{
		$source = $this->clientRepository->get($internalId);
		if ($source === null) {
			throw new RuntimeException('El cliente que intentas clonar no existe.');
		}

		$created = $this->clientRepository->create($source->wildcard);

		try {
			$this->clientRepository->update($created['client']->internalId, $source->toUpdatePayload());
		} catch (Throwable $e) {
			try {
				$this->clientRepository->delete($created['client']->internalId);
			} catch (Throwable) {
			}

			throw $e;
		}

		return $created;
	}
}
