<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Authentication\Application\LoginRateLimiter;
use App\Shared\Infrastructure\ClientIpResolver;
use App\Shared\Infrastructure\RateLimitExceededException;
use App\Tests\Unit\Support\FakeRateLimiter;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;

$request = new Request(
	new UrlScript('https://panel.test/login'),
	[],
	[],
	[],
	[],
	'GET',
	'203.0.113.7',
);

$limiter = new FakeRateLimiter();
$loginLimiter = new LoginRateLimiter($limiter, new ClientIpResolver(''), '3', '300');

$loginLimiter->assertAllowed($request, 'start');
Assert::same('admin_login:start:203.0.113.7', $limiter->lastKey);

$limiter->limited = true;
$limiter->retryAfter = 42;

try {
	$loginLimiter->assertAllowed($request, 'callback');
	Assert::fail('Se esperaba RateLimitExceededException.');
} catch (RateLimitExceededException $e) {
	Assert::same(42, $e->retryAfter);
}

Assert::same('admin_login:callback:203.0.113.7', $limiter->lastKey);
