<?php declare(strict_types=1);

namespace App\ClientManagement\Domain;

use App\ClientManagement\Domain\ValueObjects\Preview;
use App\ClientManagement\Domain\ValueObjects\RateLimit;
use App\ClientManagement\Domain\ValueObjects\Scripting;
use App\ClientManagement\Domain\ValueObjects\Whitelist;


final class Client
{
	/**
	 * @param array<string, mixed> $errorHandling
	 * @param list<array<string, mixed>> $headerRules
	 */
	public function __construct(
		public readonly string $internalId,
		public readonly string $cryptId,
		public readonly string $kind,
		public readonly bool $wildcard,
		public readonly int $configVersion,
		public readonly Whitelist $whitelist,
		public readonly RateLimit $rateLimit,
		public readonly int $maxScriptingBodyBytes,
		public readonly Scripting $scripting,
		public readonly array $errorHandling,
		public readonly array $headerRules,
		public readonly Preview $preview,
	)
	{
	}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		$errorHandling = $data['error_handling'] ?? [];
		$headerRules = $data['header_rules'] ?? [];

		return new self(
			internalId: self::stringField($data, 'internal_id'),
			cryptId: self::stringField($data, 'crypt_id'),
			kind: self::stringField($data, 'kind'),
			wildcard: (bool) ($data['wildcard'] ?? false),
			configVersion: (int) ($data['config_version'] ?? 1),
			whitelist: Whitelist::fromApiResponse($data['whitelist'] ?? []),
			rateLimit: RateLimit::fromArray(is_array($data['rate_limit'] ?? null) ? $data['rate_limit'] : []),
			maxScriptingBodyBytes: (int) ($data['max_scripting_body_bytes'] ?? 5_242_880),
			scripting: Scripting::fromApiResponse($data),
			errorHandling: is_array($errorHandling) ? $errorHandling : [],
			headerRules: self::listOfArrays($headerRules),
			preview: Preview::fromApiResponse(is_array($data['preview'] ?? null) ? $data['preview'] : []),
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
			'whitelist' => $this->whitelist->entries(),
			'rate_limit' => $this->rateLimit->toArray(),
			'max_scripting_body_bytes' => $this->maxScriptingBodyBytes,
			'scripting' => $this->scripting->toUpdatePayload(),
			'error_handling' => $this->errorHandling,
			'header_rules' => $this->headerRules,
			'preview' => $this->preview->toArray(),
		];
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toUpdatePayload(): array
	{
		return [
			'wildcard' => $this->wildcard,
			'whitelist' => $this->whitelist->entries(),
			'rate_limit' => $this->rateLimit->toArray(),
			'max_scripting_body_bytes' => $this->maxScriptingBodyBytes,
			'scripting' => $this->scripting->toUpdatePayload(),
			'error_handling' => $this->errorHandling,
			'header_rules' => $this->headerRules,
			'preview' => $this->preview->toArray(),
		];
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private static function stringField(array $data, string $key): string
	{
		$value = $data[$key] ?? null;

		return is_string($value) ? $value : '';
	}


	/**
	 * @return list<array<string, mixed>>
	 */
	private static function listOfArrays(mixed $value): array
	{
		if (!is_array($value)) {
			return [];
		}

		$result = [];
		foreach ($value as $item) {
			if (is_array($item)) {
				$result[] = $item;
			}
		}

		return $result;
	}
}
