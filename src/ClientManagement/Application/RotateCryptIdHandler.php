<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\ClientRepository;


final class RotateCryptIdHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return array{new_crypt_id: string, rotated_at: string}
	 */
	public function handle(string $cryptId): array
	{
		return $this->clientRepository->rotateCryptId($cryptId);
	}
}
