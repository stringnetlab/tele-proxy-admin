<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\ClientManagement\Domain\ValueObjects\CryptId;
use App\ClientManagement\Domain\ValueObjects\Preview;
use App\ClientManagement\Domain\ValueObjects\RateLimit;
use App\ClientManagement\Domain\ValueObjects\Scripting;
use App\ClientManagement\Domain\ValueObjects\Whitelist;
use Tester\Assert;

// CryptId
$cryptId = new CryptId('Abc123_-XYZ9');
Assert::same('Abc123_-XYZ9', (string) $cryptId);
Assert::true($cryptId->equals(new CryptId('Abc123_-XYZ9')));
Assert::false($cryptId->equals(new CryptId('Abc123_-XYZ0')));
Assert::exception(static fn () => new CryptId('corto'), InvalidArgumentException::class);
Assert::exception(static fn () => new CryptId('Abc123_-XYZ.'), InvalidArgumentException::class);

// Whitelist
$whitelist = Whitelist::fromLines("example.com\n\n 8.8.8.8 \r\nexample.com\n");
Assert::same(['example.com', '8.8.8.8'], $whitelist->entries());
Assert::same("example.com\n8.8.8.8", $whitelist->toText());

Assert::same(['example.com:8443'], (new Whitelist(['example.com:8443']))->entries());
Assert::same(['[2001:4860:4860::8888]:443'], (new Whitelist(['[2001:4860:4860::8888]:443']))->entries());

Assert::exception(static fn () => new Whitelist(['10.0.0.1']), InvalidArgumentException::class);
Assert::exception(static fn () => new Whitelist(['127.0.0.1']), InvalidArgumentException::class);
Assert::exception(static fn () => new Whitelist(['localhost']), InvalidArgumentException::class);
Assert::exception(static fn () => new Whitelist(['http://example.com']), InvalidArgumentException::class);
Assert::exception(static fn () => new Whitelist(['example.com:0']), InvalidArgumentException::class);
Assert::exception(static fn () => new Whitelist(['example.com:65536']), InvalidArgumentException::class);
Assert::exception(static fn () => new Whitelist(['2001:4860:4860::8888']), InvalidArgumentException::class);

$tooMany = [];
for ($i = 0; $i < 101; $i++) {
	$tooMany[] = 'host' . $i . '.example.com';
}
Assert::exception(static fn () => new Whitelist($tooMany), InvalidArgumentException::class);

// RateLimit
$defaults = RateLimit::fromArray([]);
Assert::same(50, $defaults->maxRequests);
Assert::same(60, $defaults->windowSeconds);
Assert::same(['max_requests' => 7, 'window_seconds' => 90], RateLimit::fromArray([
	'max_requests' => '7',
	'window_seconds' => '90',
])->toArray());
Assert::exception(static fn () => new RateLimit(0, 60), InvalidArgumentException::class);
Assert::exception(static fn () => new RateLimit(10001, 60), InvalidArgumentException::class);
Assert::exception(static fn () => new RateLimit(10, 0), InvalidArgumentException::class);
Assert::exception(static fn () => new RateLimit(10, 3601), InvalidArgumentException::class);

// Scripting
Assert::same('sha256:' . hash('sha256', 'return 1;'), Scripting::hash('return 1;'));

$scripting = Scripting::fromApiResponse([
	'scripting_enabled' => true,
	'scripting_code' => 'return 1;',
	'scripting_code_hash' => 'sha256:viejo',
	'scripting_expression' => 'ctx.secure',
]);
Assert::true($scripting->enabled);
Assert::same('return 1;', $scripting->code);
Assert::same('ctx.secure', $scripting->expression);
Assert::same(
	[
		'enabled' => true,
		'code' => 'return 1;',
		'code_hash' => Scripting::hash('return 1;'),
		'expression' => 'ctx.secure',
	],
	$scripting->toUpdatePayload(),
);

$sinCodigo = new Scripting(false, '', 'sha256:existente', '');
Assert::same('sha256:existente', $sinCodigo->toUpdatePayload()['code_hash']);

Assert::exception(
	static fn () => new Scripting(false, '', '', str_repeat('a', 4097)),
	InvalidArgumentException::class,
);

// Preview
$preview = Preview::fromApiResponse([]);
Assert::false($preview->enabled);
Assert::true($preview->metaEnabled);
Assert::true($preview->capabilityMeta);
Assert::true($preview->capabilityContent);
Assert::false($preview->capabilityScreenshot);
Assert::false($preview->capabilityPdf);
Assert::same(60, $preview->rateLimit->maxRequests);
Assert::same(60, $preview->rateLimit->windowSeconds);
Assert::same(86400, $preview->ttlDefaultSeconds);
Assert::same(2678400, $preview->ttlMaxSeconds);
Assert::same(45000, $preview->maxRenderTimeoutMs);
Assert::same(2097152, $preview->maxBodyBytes);
Assert::true($preview->screenshotMaxFullPage);
Assert::same(1920, $preview->viewportWidth);
Assert::same(1080, $preview->viewportHeight);

$roundTrip = Preview::fromApiResponse($preview->toArray());
Assert::same($preview->toArray(), $roundTrip->toArray());

Assert::exception(
	static fn () => Preview::fromApiResponse([
		'ttl_default_seconds' => 100,
		'ttl_max_seconds' => 50,
	]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => Preview::fromApiResponse(['ttl_max_seconds' => 2678401]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => Preview::fromApiResponse(['max_render_timeout_ms' => 0]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => Preview::fromApiResponse(['max_body_bytes' => 10485761]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => Preview::fromApiResponse(['screenshot' => ['default_viewport' => [0, 1080]]]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => Preview::fromApiResponse(['screenshot' => ['default_viewport' => [1920, 4097]]]),
	InvalidArgumentException::class,
);
