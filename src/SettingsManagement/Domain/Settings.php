<?php declare(strict_types=1);

namespace App\SettingsManagement\Domain;

use InvalidArgumentException;


final class Settings
{
	public const FIELDS = [
		'upstream_user_agent' => 'upstreamUserAgent',
		'upstream_timeout_secs' => 'upstreamTimeoutSecs',
		'max_response_bytes' => 'maxResponseBytes',
		'cacheable_size_bytes' => 'cacheableSizeBytes',
		'stale_cache_ttl_secs' => 'staleCacheTtlSecs',
		'cache_ttl_images_secs' => 'cacheTtlImagesSecs',
		'cache_ttl_assets_secs' => 'cacheTtlAssetsSecs',
		'cache_ttl_default_secs' => 'cacheTtlDefaultSecs',
		'lua_timeout_ms' => 'luaTimeoutMs',
		'lua_memory_limit_mb' => 'luaMemoryLimitMb',
		'webhook_timeout_ms' => 'webhookTimeoutMs',
		'fallback_timeout_ms' => 'fallbackTimeoutMs',
		'verbose_errors' => 'verboseErrors',
	];


	public function __construct(
		public readonly string $upstreamUserAgent,
		public readonly int $upstreamTimeoutSecs,
		public readonly int $maxResponseBytes,
		public readonly int $cacheableSizeBytes,
		public readonly int $staleCacheTtlSecs,
		public readonly int $cacheTtlImagesSecs,
		public readonly int $cacheTtlAssetsSecs,
		public readonly int $cacheTtlDefaultSecs,
		public readonly int $luaTimeoutMs,
		public readonly int $luaMemoryLimitMb,
		public readonly int $webhookTimeoutMs,
		public readonly int $fallbackTimeoutMs,
		public readonly bool $verboseErrors,
	) {}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		$userAgent = $data['upstream_user_agent'] ?? null;

		return new self(
			upstreamUserAgent: is_string($userAgent) ? $userAgent : '',
			upstreamTimeoutSecs: self::intField($data, 'upstream_timeout_secs'),
			maxResponseBytes: self::intField($data, 'max_response_bytes'),
			cacheableSizeBytes: self::intField($data, 'cacheable_size_bytes'),
			staleCacheTtlSecs: self::intField($data, 'stale_cache_ttl_secs'),
			cacheTtlImagesSecs: self::intField($data, 'cache_ttl_images_secs'),
			cacheTtlAssetsSecs: self::intField($data, 'cache_ttl_assets_secs'),
			cacheTtlDefaultSecs: self::intField($data, 'cache_ttl_default_secs'),
			luaTimeoutMs: self::intField($data, 'lua_timeout_ms'),
			luaMemoryLimitMb: self::intField($data, 'lua_memory_limit_mb'),
			webhookTimeoutMs: self::intField($data, 'webhook_timeout_ms'),
			fallbackTimeoutMs: self::intField($data, 'fallback_timeout_ms'),
			verboseErrors: (bool) ($data['verbose_errors'] ?? false),
		);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'upstream_user_agent' => $this->upstreamUserAgent,
			'upstream_timeout_secs' => $this->upstreamTimeoutSecs,
			'max_response_bytes' => $this->maxResponseBytes,
			'cacheable_size_bytes' => $this->cacheableSizeBytes,
			'stale_cache_ttl_secs' => $this->staleCacheTtlSecs,
			'cache_ttl_images_secs' => $this->cacheTtlImagesSecs,
			'cache_ttl_assets_secs' => $this->cacheTtlAssetsSecs,
			'cache_ttl_default_secs' => $this->cacheTtlDefaultSecs,
			'lua_timeout_ms' => $this->luaTimeoutMs,
			'lua_memory_limit_mb' => $this->luaMemoryLimitMb,
			'webhook_timeout_ms' => $this->webhookTimeoutMs,
			'fallback_timeout_ms' => $this->fallbackTimeoutMs,
			'verbose_errors' => $this->verboseErrors,
		];
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private static function intField(array $data, string $field): int
	{
		$value = $data[$field] ?? null;
		if (is_int($value)) {
			return $value;
		}

		if (is_string($value) && preg_match('/^-?\d+$/', $value) === 1) {
			return (int) $value;
		}

		throw new InvalidArgumentException('Falta el ajuste ' . $field . ' o no es numérico.');
	}
}
