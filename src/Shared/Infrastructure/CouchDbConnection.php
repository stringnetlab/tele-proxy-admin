<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;


class CouchDbConnection
{
	private readonly Client $httpClient;
	private readonly string $database;


	public function __construct(
		string $couchDbUrl,
		string $database,
	)
	{
		$this->database = $database;
		$this->httpClient = new Client([
			'base_uri' => rtrim($couchDbUrl, '/'),
			'timeout' => 10,
			'connect_timeout' => 5,
			'http_errors' => false,
			'headers' => [
				'Accept' => 'application/json',
				'Content-Type' => 'application/json',
			],
		]);
	}


	public function get(string $docId): ?array
	{
		$response = $this->httpClient->get('/' . $this->database . '/' . urlencode($docId));

		if ($response->getStatusCode() === 404) {
			return null;
		}

		return json_decode((string) $response->getBody(), true);
	}


	public function put(string $docId, array $data): bool
	{
		$response = $this->httpClient->put(
			'/' . $this->database . '/' . urlencode($docId),
			['json' => $data],
		);

		return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
	}


	public function delete(string $docId, string $rev): bool
	{
		$response = $this->httpClient->delete(
			'/' . $this->database . '/' . urlencode($docId) . '?rev=' . urlencode($rev),
		);

		return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
	}


	/**
	 * @param array<string, mixed> $query
	 */
	public function find(array $query): array
	{
		$response = $this->httpClient->post(
			'/' . $this->database . '/_find',
			['json' => $query],
		);

		$body = json_decode((string) $response->getBody(), true);
		return $body['docs'] ?? [];
	}


	public function logEvent(string $event, string $userEmail, array $data = []): bool
	{
		$doc = array_merge([
			'type' => 'audit_log',
			'event' => $event,
			'user_email' => $userEmail,
			'timestamp' => gmdate('c'),
		], $data);

		$docId = 'log_' . bin2hex(random_bytes(16));
		return $this->put($docId, $doc);
	}
}
