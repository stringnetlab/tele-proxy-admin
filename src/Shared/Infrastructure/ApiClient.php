<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;


class ApiClient
{
	private Client $httpClient;


	public function __construct(
		private readonly string $apiUrl,
	)
	{
		$this->httpClient = $this->buildClient();
	}


	/**
	 * @param array<string, mixed> $options
	 */
	public function get(string $path, array $options = []): ResponseInterface
	{
		return $this->send('GET', $path, $options);
	}


	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 */
	public function post(string $path, array $data = [], array $options = []): ResponseInterface
	{
		$options['json'] = $data;

		return $this->send('POST', $path, $options);
	}


	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 */
	public function put(string $path, array $data = [], array $options = []): ResponseInterface
	{
		$options['json'] = $data;

		return $this->send('PUT', $path, $options);
	}


	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 */
	public function patch(string $path, array $data = [], array $options = []): ResponseInterface
	{
		$options['json'] = $data;

		return $this->send('PATCH', $path, $options);
	}


	/**
	 * @param array<string, mixed> $options
	 */
	public function delete(string $path, array $options = []): ResponseInterface
	{
		return $this->send('DELETE', $path, $options);
	}


	/**
	 * @param array<string, mixed> $options
	 */
	public function send(string $method, string $path, array $options = []): ResponseInterface
	{
		try {
			return $this->httpClient->request($method, $path, $options);
		} catch (GuzzleException $e) {
			throw new ApiError(0, 'transport_error', ApiError::defaultMessage(0), null, null, $e);
		}
	}


	/**
	 * Envía la petición y lanza ApiError ante cualquier respuesta >= 400.
	 *
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function json(string $method, string $path, array $options = []): array
	{
		$response = $this->send($method, $path, $options);

		if ($response->getStatusCode() >= 400) {
			throw ApiError::fromResponse($response);
		}

		$body = (string) $response->getBody();
		if ($body === '') {
			return [];
		}

		$data = json_decode($body, true);
		if (!is_array($data)) {
			throw new ApiError(
				$response->getStatusCode(),
				'invalid_response',
				'Respuesta inválida del servicio de administración.',
			);
		}

		/** @var array<string, mixed> $data */
		return $data;
	}


	public function withSessionCookie(string $cookieValue): self
	{
		$clone = clone $this;
		$clone->httpClient = $this->buildClient($cookieValue);

		return $clone;
	}


	public function getApiUrl(): string
	{
		return $this->apiUrl;
	}


	private function buildClient(?string $sessionCookie = null): Client
	{
		$headers = [
			'Accept' => 'application/json',
			'X-Admin-UI' => '1',
			'User-Agent' => 'teleproxy-admin/1.0',
		];

		if ($sessionCookie !== null && $sessionCookie !== '') {
			$headers['Cookie'] = 'teleproxy_admin=' . $sessionCookie;
		}

		return new Client([
			'base_uri' => rtrim($this->apiUrl, '/'),
			'timeout' => 30,
			'connect_timeout' => 5,
			'http_errors' => false,
			'allow_redirects' => false,
			'headers' => $headers,
		]);
	}
}
