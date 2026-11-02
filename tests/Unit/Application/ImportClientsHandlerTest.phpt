<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\ClientManagement\Application\ImportClientsHandler;
use App\ClientManagement\Domain\ValueObjects\Scripting;
use App\Shared\Infrastructure\ApiError;
use App\Tests\Unit\Support\FakeClientRepository;
use Tester\Assert;

$entry = [
	'wildcard' => true,
	'whitelist' => ['example.com', 123, ' 8.8.8.8 '],
	'rate_limit' => ['max_requests' => '25', 'window_seconds' => '60'],
	'max_scripting_body_bytes' => '4096',
	'scripting' => ['enabled' => 1, 'code' => 'return 42;', 'expression' => 'ctx.secure'],
	'error_handling' => ['mode' => 'pass'],
	'header_rules' => [['name' => 'X-A'], 'no-array', ['name' => 'X-B']],
	'preview' => ['enabled' => true],
];

$repository = new FakeClientRepository();
$results = (new ImportClientsHandler($repository))->handle([$entry]);

Assert::same([true], $repository->createCalls);
Assert::same(
	[
		[
			'internalId' => 'int-created',
			'payload' => [
				'whitelist' => ['example.com', ' 8.8.8.8 '],
				'rate_limit' => ['max_requests' => 25, 'window_seconds' => 60],
				'max_scripting_body_bytes' => 4096,
				'scripting' => [
					'enabled' => true,
					'code' => 'return 42;',
					'expression' => 'ctx.secure',
					'code_hash' => Scripting::hash('return 42;'),
				],
				'error_handling' => ['mode' => 'pass'],
				'header_rules' => [['name' => 'X-A'], ['name' => 'X-B']],
				'preview' => ['enabled' => true],
			],
		],
	],
	$repository->updateCalls,
);
Assert::same(
	[['crypt_id' => 'cryptCreated', 'status' => 'created', 'token' => 'tok-created']],
	$results,
);
Assert::same([], $repository->deleteCalls);

$minimal = new FakeClientRepository();
(new ImportClientsHandler($minimal))->handle([[]]);
Assert::same(
	[
		[
			'internalId' => 'int-created',
			'payload' => [
				'scripting' => ['enabled' => false, 'code' => '', 'expression' => ''],
			],
		],
	],
	$minimal->updateCalls,
);

$createFails = new FakeClientRepository();
$createFails->createError = new ApiError(409, 'conflict', 'Ya existe.');
$failed = (new ImportClientsHandler($createFails))->handle([$entry]);
Assert::same(
	[['crypt_id' => '', 'status' => 'failed', 'message' => 'Ya existe.']],
	$failed,
);
Assert::same([], $createFails->updateCalls);
Assert::same([], $createFails->deleteCalls);

$updateFails = new FakeClientRepository();
$updateFails->updateError = new ApiError(400, 'invalid_config', 'Configuración inválida.');
$compensated = (new ImportClientsHandler($updateFails))->handle([$entry]);
Assert::same(
	[['crypt_id' => 'cryptCreated', 'status' => 'failed', 'message' => 'Configuración inválida.']],
	$compensated,
);
Assert::same(['int-created'], $updateFails->deleteCalls);

$unexpected = new FakeClientRepository();
$unexpected->updateError = new RuntimeException('boom');
$generic = (new ImportClientsHandler($unexpected))->handle([$entry]);
Assert::same('failed', $generic[0]['status']);
Assert::same('No se pudo aplicar la configuración importada.', $generic[0]['message']);
Assert::same(['int-created'], $unexpected->deleteCalls);
