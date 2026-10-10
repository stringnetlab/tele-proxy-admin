<?php declare(strict_types=1);

namespace App\ClientManagement\Domain;

use App\ClientManagement\Domain\ValueObjects\RateLimit;
use App\ClientManagement\Domain\ValueObjects\Whitelist;


final class Client
{
	/**
	 * @param string[] $whitelist
	 * @param array<string, mixed> $scripting
	 * @param array<string, mixed> $errorHandling
	 */
	public function __construct(
		public readonly string $internalId,
		public readonly string $cryptId,
		public readonly string $kind,
		public readonly bool $wildcard,
		public readonly int $configVersion,
		public readonly array $whitelist,
		public readonly RateLimit $rateLimit,
		public readonly int $maxScriptingBodyBytes,
		public readonly array $scripting,
		public readonly array $errorHandling,
	)
	{
	}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		return new self(
			internalId: $data['internal_id'] ?? '',
			cryptId: $data['crypt_id'] ?? '',
			kind: $data['kind'] ?? 'client',
			wildcard: (bool) ($data['wildcard'] ?? false),
			configVersion: (int) ($data['config_version'] ?? 1),
			whitelist: $data['whitelist'] ?? [],
			rateLimit: RateLimit::fromArray($data['rate_limit'] ?? []),
			maxScriptingBodyBytes: (int) ($data['max_scripting_body_bytes'] ?? 5242880),
			scripting: $data['scripting'] ?? [],
			errorHandling: $data['error_handling'] ?? [],
		);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'internal_id' => $this->internalId,
			'crypt_id' => $this->cryptId,
			'kind' => $this->kind,
			'wildcard' => $this->wildcard,
			'config_version' => $this->configVersion,
			'whitelist' => $this->whitelist,
			'rate_limit' => $this->rateLimit->toArray(),
			'max_scripting_body_bytes' => $this->maxScriptingBodyBytes,
			'scripting' => $this->scripting,
			'error_handling' => $this->errorHandling,
		];
	}
}
