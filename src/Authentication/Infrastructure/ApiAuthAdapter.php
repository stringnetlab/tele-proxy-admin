<?php declare(strict_types=1);

namespace App\Authentication\Infrastructure;

use App\Authentication\Domain\GoogleCallbackResult;
use App\Shared\Infrastructure\ApiClient;
use App\Shared\Infrastructure\ApiError;


class ApiAuthAdapter
{
	public function __construct(
		private readonly ApiClient $apiClient,
	)
	{
	}


	public function getGoogleAuthUrl(): string
	{
		$data = $this->apiClient->json('GET', '/api/v1/admin/auth/google/url');
		$url = $data['url'] ?? null;

		if (!is_string($url) || $url === '') {
			throw new ApiError(502, 'invalid_response', 'El servicio de administración no devolvió una URL de login válida.');
		}

		return $url;
	}


	public function exchangeGoogleCallback(string $code, string $state): GoogleCallbackResult
	{
		$response = $this->apiClient->get('/api/v1/admin/auth/google/callback', [
			'query' => [
				'code' => $code,
				'state' => $state,
			],
			'allow_redirects' => false,
		]);

		$cookie = null;
		$maxAge = null;

		foreach ($response->getHeader('Set-Cookie') as $line) {
			if ($cookie === null && preg_match('/\bteleproxy_admin=([^;]*)/', $line, $match) === 1) {
				$cookie = $match[1];
			}

			if ($maxAge === null && preg_match('/\bMax-Age=(\d+)/i', $line, $match) === 1) {
				$maxAge = (int) $match[1];
			}
		}

		if ($response->getStatusCode() === 302 && is_string($cookie) && $cookie !== '') {
			return new GoogleCallbackResult(302, $cookie, $maxAge);
		}

		$decoded = json_decode((string) $response->getBody(), true);
		$errorCode = is_array($decoded) && isset($decoded['error']) && is_string($decoded['error'])
			? $decoded['error']
			: 'api_error';

		return new GoogleCallbackResult(
			status: $response->getStatusCode(),
			sessionCookie: null,
			maxAgeSeconds: null,
			errorCode: $errorCode,
			retryAfter: $this->retryAfter($response->getHeaderLine('Retry-After')),
		);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function getMe(string $sessionCookie): array
	{
		return $this->apiClient
			->withSessionCookie($sessionCookie)
			->json('GET', '/api/v1/admin/me');
	}


	public function logout(string $sessionCookie): void
	{
		$response = $this->apiClient
			->withSessionCookie($sessionCookie)
			->post('/api/v1/admin/auth/logout');

		if ($response->getStatusCode() >= 400 && $response->getStatusCode() !== 401) {
			throw ApiError::fromResponse($response);
		}
	}


	private function retryAfter(string $header): ?int
	{
		$header = trim($header);
		if ($header === '') {
			return null;
		}

		if (ctype_digit($header)) {
			return (int) $header;
		}

		$timestamp = strtotime($header);

		return $timestamp === false ? null : max(0, $timestamp - time());
	}
}
