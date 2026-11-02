<?php declare(strict_types=1);

namespace App\Tests\Unit\Support;

use App\Shared\Infrastructure\ApiClient;
use RuntimeException;
use Throwable;


final class FakeApiClient extends ApiClient
{
	/** @var list<array{method: string, path: string, options: array<string, mixed>}> */
	public array $calls = [];

	/** @var list<array<string, mixed>|Throwable> */
	private array $responses = [];

	public ?string $cookie = null;


	public function __construct()
	{
	}


	/**
	 * @param array<string, mixed>|Throwable $response
	 */
	public function queue(array|Throwable $response): void
	{
		$this->responses[] = $response;
	}


	/**
	 * @param array<string, mixed> $options
	 * @return array<string, mixed>
	 */
	public function json(string $method, string $path, array $options = []): array
	{
		$this->calls[] = [
			'method' => $method,
			'path' => $path,
			'options' => $options,
		];

		$response = array_shift($this->responses);
		if ($response instanceof Throwable) {
			throw $response;
		}

		if (!is_array($response)) {
			throw new RuntimeException('FakeApiClient no tiene respuestas encoladas.');
		}

		return $response;
	}


	public function withSessionCookie(string $cookieValue): self
	{
		$this->cookie = $cookieValue;

		return $this;
	}
}
