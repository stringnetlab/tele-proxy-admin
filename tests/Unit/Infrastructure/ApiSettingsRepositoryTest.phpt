<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\SettingsManagement\Infrastructure\ApiSettingsRepository;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiSession;
use App\Tests\Unit\Support\FakeApiClient;
use Tester\Assert;

$settings = [
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
	'verbose_errors' => false,
];

$api = new FakeApiClient();
$session = new ApiSession();
$repository = new ApiSettingsRepository($api, $session);

$api->queue($settings);
$loaded = $repository->get();
Assert::same('GET', $api->calls[0]['method']);
Assert::same('/api/v1/admin/settings', $api->calls[0]['path']);
Assert::same(30, $loaded->upstreamTimeoutSecs);
Assert::null($api->cookie);

$session->setCookie('cookie-secreto');
$updatedRaw = $settings;
$updatedRaw['upstream_timeout_secs'] = 45;
$api->queue($updatedRaw);
$updated = $repository->patch(['upstream_timeout_secs' => 45]);
Assert::same('cookie-secreto', $api->cookie);
Assert::same('PATCH', $api->calls[1]['method']);
Assert::same('/api/v1/admin/settings', $api->calls[1]['path']);
Assert::same(['json' => ['upstream_timeout_secs' => 45]], $api->calls[1]['options']);
Assert::same(45, $updated->upstreamTimeoutSecs);

$api->queue(['upstream_timeout_secs' => 30]);
Assert::exception(static fn () => $repository->get(), ApiError::class);
