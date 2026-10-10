<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;


final class CloneClientHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	public function handle(string $cryptId, string $newName): Client
	{
		$source = $this->clientRepository->get($cryptId);
		if ($source === null) {
			throw new \RuntimeException("Client $cryptId not found");
		}

		$data = $source->toArray();
		$data['name'] = $newName;
		unset($data['crypt_id'], $data['created_at'], $data['updated_at'], $data['config_version']);

		return $this->clientRepository->create($data);
	}
}
