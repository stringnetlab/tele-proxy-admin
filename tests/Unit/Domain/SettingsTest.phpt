<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\SettingsManagement\Domain\Settings;
use Tester\Assert;

$raw = [
	'upstream_user_agent' => 'teleproxy/1.0',
	'upstream_timeout_secs' => 30,
	'max_response_bytes' => '1048576',
	'cacheable_size_bytes' => 524288,
	'stale_cache_ttl_secs' => 3600,
	'cache_ttl_images_secs' => 86400,
	'cache_ttl_assets_secs' => 1800,
	'cache_ttl_default_secs' => 600,
	'lua_timeout_ms' => 500,
	'lua_memory_limit_mb' => 64,
	'webhook_timeout_ms' => 2000,
	'fallback_timeout_ms' => 1500,
	'verbose_errors' => true,
];

$settings = Settings::fromApiResponse($raw);

Assert::same('teleproxy/1.0', $settings->upstreamUserAgent);
Assert::same(30, $settings->upstreamTimeoutSecs);
Assert::same(1048576, $settings->maxResponseBytes);
Assert::same(524288, $settings->cacheableSizeBytes);
Assert::same(3600, $settings->staleCacheTtlSecs);
Assert::same(86400, $settings->cacheTtlImagesSecs);
Assert::same(1800, $settings->cacheTtlAssetsSecs);
Assert::same(600, $settings->cacheTtlDefaultSecs);
Assert::same(500, $settings->luaTimeoutMs);
Assert::same(64, $settings->luaMemoryLimitMb);
Assert::same(2000, $settings->webhookTimeoutMs);
Assert::same(1500, $settings->fallbackTimeoutMs);
Assert::true($settings->verboseErrors);

Assert::same(
	[
		'upstream_user_agent' => 'teleproxy/1.0',
		'upstream_timeout_secs' => 30,
		'max_response_bytes' => 1048576,
		'cacheable_size_bytes' => 524288,
		'stale_cache_ttl_secs' => 3600,
		'cache_ttl_images_secs' => 86400,
		'cache_ttl_assets_secs' => 1800,
		'cache_ttl_default_secs' => 600,
		'lua_timeout_ms' => 500,
		'lua_memory_limit_mb' => 64,
		'webhook_timeout_ms' => 2000,
		'fallback_timeout_ms' => 1500,
		'verbose_errors' => true,
	],
	$settings->toArray(),
);

$minimal = Settings::fromApiResponse([
	'upstream_timeout_secs' => 1,
	'max_response_bytes' => 1,
	'cacheable_size_bytes' => 1,
	'stale_cache_ttl_secs' => 1,
	'cache_ttl_images_secs' => 1,
	'cache_ttl_assets_secs' => 1,
	'cache_ttl_default_secs' => 1,
	'lua_timeout_ms' => 1,
	'lua_memory_limit_mb' => 1,
	'webhook_timeout_ms' => 1,
	'fallback_timeout_ms' => 1,
]);
Assert::same('', $minimal->upstreamUserAgent);
Assert::false($minimal->verboseErrors);

Assert::exception(
	static fn () => Settings::fromApiResponse(['upstream_timeout_secs' => 30]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => Settings::fromApiResponse([
		'upstream_timeout_secs' => 'treinta',
		'max_response_bytes' => 1,
		'cacheable_size_bytes' => 1,
		'stale_cache_ttl_secs' => 1,
		'cache_ttl_images_secs' => 1,
		'cache_ttl_assets_secs' => 1,
		'cache_ttl_default_secs' => 1,
		'lua_timeout_ms' => 1,
		'lua_memory_limit_mb' => 1,
		'webhook_timeout_ms' => 1,
		'fallback_timeout_ms' => 1,
	]),
	InvalidArgumentException::class,
);
