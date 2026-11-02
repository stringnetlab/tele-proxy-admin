<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Shared\Infrastructure\TokenVault;
use App\Tests\Unit\Support\FakeValkeyConnection;
use Tester\Assert;

$valkey = new FakeValkeyConnection();
$vault = new TokenVault($valkey);

$handle = $vault->store(['reason' => 'alta', 'entries' => ['uno', 'dos']]);

Assert::true(preg_match('/^[a-f0-9]{64}$/', $handle) === 1);
Assert::same(300, $valkey->ttls['admin_ui_token:' . $handle]);
Assert::same(
	['reason' => 'alta', 'entries' => ['uno', 'dos']],
	$vault->consume($handle),
);
Assert::null($vault->consume($handle));

$second = $vault->store(['resultado' => 'ok']);
Assert::null($vault->consume('handle-invalido'));
Assert::null($vault->consume(str_repeat('g', 64)));
Assert::same(['resultado' => 'ok'], $vault->consume($second));
Assert::same([], $valkey->data);
