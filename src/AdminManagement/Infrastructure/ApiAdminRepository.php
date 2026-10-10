<?php declare(strict_types=1);

namespace App\AdminManagement\Infrastructure;

use App\AdminManagement\Domain\AdminRepository;
use App\Shared\Infrastructure\ApiClient;


class ApiAdminRepository implements AdminRepository
{
	public function __construct(
		private readonly ApiClient $apiClient,
	)
	{
	}


	public function list(): array
	{
		$response = $this->apiClient->get('/api/v1/admin/admins');

		return json_decode((string) $response->getBody(), true) ?? [];
	}


	public function get(string $email): ?array
	{
		$admins = $this->list();

		foreach ($admins as $admin) {
			if (($admin['email'] ?? '') === $email) {
				return $admin;
			}
		}

		return null;
	}


	public function add(string $email): array
	{
		$response = $this->apiClient->post('/api/v1/admin/admins', [
			'email' => $email,
		]);

		return json_decode((string) $response->getBody(), true) ?? [];
	}


	public function toggle(string $email, bool $active): array
	{
		$response = $this->apiClient->patch('/api/v1/admin/admins/' . urlencode($email), [
			'active' => $active,
		]);

		return json_decode((string) $response->getBody(), true) ?? [];
	}


	public function delete(string $email): void
	{
		$this->apiClient->delete('/api/v1/admin/admins/' . urlencode($email));
	}
}
