<?php declare(strict_types=1);

namespace App\ClientManagement\Infrastructure;

use App\ClientManagement\Domain\ClientRepository;
use App\Shared\Infrastructure\ApiClient;


class ApiClientRepository implements ClientRepository
{
	public function __construct(
		private readonly ApiClient $apiClient,
	)
	{
	}


	public function list(int $limit = 50, int $skip = 0): array
	{
		$response = $this->apiClient->get('/api/v1/admin/clients', [
			'query' => [
				'limit' => $limit,
				'skip' => $skip,
			],
		]);

		$body = json_decode((string) $response->getBody(), true);

		return [
			'clients' => $body['clients'] ?? [],
			'total' => $body['total'] ?? 0,
		];
	}


	public function get(string $id): ?array
	{
		$response = $this->apiClient->get('/api/v1/admin/clients/' . urlencode($id));

		if ($response->getStatusCode() === 404) {
			return null;
		}

		return json_decode((string) $response->getBody(), true);
	}


	public function create(array $data = []): array
	{
		$response = $this->apiClient->post('/api/v1/admin/clients', $data);

		return json_decode((string) $response->getBody(), true);
	}


	public function update(string $id, array $data): array
	{
		$response = $this->apiClient->put('/api/v1/admin/clients/' . urlencode($id), $data);

		return json_decode((string) $response->getBody(), true);
	}


	public function delete(string $id): void
	{
		$this->apiClient->delete('/api/v1/admin/clients/' . urlencode($id));
	}


	public function rotateToken(string $id): array
	{
		$response = $this->apiClient->post('/api/v1/admin/clients/' . urlencode($id) . '/rotate-token');

		return json_decode((string) $response->getBody(), true);
	}


	public function rotateCryptId(string $id): array
	{
		$response = $this->apiClient->post('/api/v1/admin/clients/' . urlencode($id) . '/rotate-id');

		return json_decode((string) $response->getBody(), true);
	}
}
