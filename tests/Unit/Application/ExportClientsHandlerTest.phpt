<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\ClientManagement\Application\ExportClientsHandler;
use App\Tests\Unit\Support\FakeClientRepository;
use Tester\Assert;

$repository = new FakeClientRepository();
$repository->clients = [
	FakeClientRepository::client('int-1', 'cryptAAAAAA1'),
	FakeClientRepository::client('int-2', 'cryptAAAAAA2'),
];

$exported = (new ExportClientsHandler($repository))->handle();

Assert::count(2, $exported);
Assert::same('cryptAAAAAA1', $exported[0]['crypt_id']);
Assert::same('standard', $exported[0]['kind']);
Assert::false($exported[0]['wildcard']);
Assert::same(['example.com'], $exported[0]['whitelist']);
Assert::same(['max_requests' => 10, 'window_seconds' => 30], $exported[0]['rate_limit']);
Assert::same(1234, $exported[0]['max_scripting_body_bytes']);
Assert::same(
	['enabled' => true, 'code' => 'return 1;', 'expression' => 'true'],
	$exported[0]['scripting'],
);
Assert::same(['mode' => 'fallback'], $exported[0]['error_handling']);
Assert::same([['name' => 'X-Test']], $exported[0]['header_rules']);
Assert::true($exported[0]['preview']['enabled']);

Assert::false(array_key_exists('token', $exported[0]));
Assert::false(array_key_exists('internal_id', $exported[0]));
Assert::false(array_key_exists('config_version', $exported[0]));

$many = new FakeClientRepository();
for ($i = 0; $i < 201; $i++) {
	$many->clients[] = FakeClientRepository::client('int-' . $i, 'crypt' . str_pad((string) $i, 6, '0', STR_PAD_LEFT));
}

$all = (new ExportClientsHandler($many))->handle();
Assert::count(201, $all);
Assert::same('crypt000000', $all[0]['crypt_id']);
Assert::same('crypt000200', $all[200]['crypt_id']);
