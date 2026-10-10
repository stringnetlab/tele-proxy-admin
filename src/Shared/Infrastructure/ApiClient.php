<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;


class ApiClient
{
	private readonly Client $httpClient;


	public function __construct(
		private readonly string $apiUrl,
	)
	{
		$this->httpClient = new Client([
			'base_uri' => rtrim($this->apiUrl, '/'),
			'timeout' => 30,
			'connect_timeout' => 5,
			'http_errors' => false,
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-Admin-UI' => '1',
			],
		]);
	}


	/**
	 * @param array<string, mixed> $options
	 */
	public function get(string $path, array $options = []): Response
	{
		return $this->httpClient->get($path, $options);
	}


	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 */
	public function post(string $path, array $data = [], array $options = []): Response
	{
		$options['json'] = $data;
		return $this->httpClient->post($path, $options);
	}


	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 */
	public function put(string $path, array $data = [], array $options = []): Response
	{
		$options['json'] = $data;
		return $this->httpClient->put($path, $options);
	}


	/**
	 * @param array<string, mixed> $data
	 * @param array<string, mixed> $options
	 */
	public function patch(string $path, array $data = [], array $options = []): Response
	{
		$options['json'] = $data;
		return $this->httpClient->patch($path, $options);
	}


	/**
	 * @param array<string, mixed> $options
	 */
	public function delete(string $path, array $options = []): Response
	{
		return $this->httpClient->delete($path, $options);
	}


	public function withSessionCookie(string $cookieValue): self
	{
		$clone = clone $this;
		$clone->httpClient = new Client([
			'base_uri' => rtrim($this->apiUrl, '/'),
			'timeout' => 30,
			'connect_timeout' => 5,
			'http_errors' => false,
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-Admin-UI' => '1',
				'Cookie' => 'teleproxy_admin=' . $cookieValue,
			],
		]);
		return $clone;
	}


	public function withMasterToken(string $token): self
	{
		$clone = clone $this;
		$clone->httpClient = new Client([
			'base_uri' => rtrim($this->apiUrl, '/'),
			'timeout' => 30,
			'connect_timeout' => 5,
			'http_errors' => false,
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
				'X-Admin-UI' => '1',
				'Authorization' => 'Bearer ' . $token,
			],
		]);
		return $clone;
	}


	public function getApiUrl(): string
	{
		return $this->apiUrl;
	}
}
