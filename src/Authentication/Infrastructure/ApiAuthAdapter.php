<?php declare(strict_types=1);

namespace App\Authentication\Infrastructure;

use App\Shared\Infrastructure\ApiClient;


class ApiAuthAdapter
{
	public function __construct(
		private readonly ApiClient $apiClient,
	)
	{
	}


	/**
	 * @return array{url: string}|null
	 */
	public function getGoogleAuthUrl(): ?array
	{
		$response = $this->apiClient->get('/api/v1/admin/auth/google/url');

		if ($response->getStatusCode() !== 200) {
			return null;
		}

		return json_decode((string) $response->getBody(), true);
	}


	/**
	 * @return array{email: string, role: string, via: string}|null
	 */
	public function getMe(): ?array
	{
		$response = $this->apiClient->get('/api/v1/admin/me');

		if ($response->getStatusCode() !== 200) {
			return null;
		}

		return json_decode((string) $response->getBody(), true);
	}


	/**
	 * @return array{email: string, role: string, via: string}|null
	 */
	public function verifyMasterToken(string $token): ?array
	{
		$client = $this->apiClient->withMasterToken($token);
		$response = $client->get('/api/v1/admin/me');

		if ($response->getStatusCode() !== 200) {
			return null;
		}

		return json_decode((string) $response->getBody(), true);
	}


	public function logout(): bool
	{
		$response = $this->apiClient->post('/api/v1/admin/auth/logout');
		return $response->getStatusCode() === 200;
	}
}
