<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Shared\Infrastructure\ClientIpResolver;
use Nette\Http\Request;
use Nette\Http\UrlScript;
use Tester\Assert;

$url = new UrlScript('https://panel.test/login');

/**
 * @param array<string, string> $headers
 */
$request = static fn (array $headers, ?string $remote): Request
	=> new Request($url, [], [], [], $headers, 'GET', $remote);

$untrusted = new ClientIpResolver('');
Assert::same(
	'198.51.100.9',
	$untrusted->resolve($request(['CF-Connecting-IP' => '1.2.3.4'], '198.51.100.9')),
);

$trusted = new ClientIpResolver('10.0.0.1');
Assert::same(
	'203.0.113.5',
	$trusted->resolve($request(['CF-Connecting-IP' => '203.0.113.5'], '10.0.0.1')),
);
Assert::same(
	'10.0.0.1',
	$trusted->resolve($request([], '10.0.0.1')),
);

$cidr = new ClientIpResolver('10.0.0.0/8');
Assert::same(
	'9.9.9.9',
	$cidr->resolve($request(['CF-Connecting-IP' => '9.9.9.9'], '10.7.8.9')),
);
Assert::same(
	'1.1.1.1',
	$cidr->resolve($request(['X-Forwarded-For' => '1.1.1.1, 10.0.0.2'], '10.0.0.1')),
);
Assert::same(
	'1.1.1.1',
	$cidr->resolve($request(['CF-Connecting-IP' => 'no-es-ip', 'X-Forwarded-For' => 'spoof, 1.1.1.1'], '10.0.0.1')),
);

Assert::same('unknown', $untrusted->resolve($request([], null)));
