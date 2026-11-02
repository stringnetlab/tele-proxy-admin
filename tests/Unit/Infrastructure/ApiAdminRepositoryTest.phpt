<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\AdminManagement\Infrastructure\ApiAdminRepository;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiSession;
use App\Tests\Unit\Support\FakeApiClient;
use Tester\Assert;

$api = new FakeApiClient();
$session = new ApiSession();
$repository = new ApiAdminRepository($api, $session);

$admin = [
	'email' => 'ops@example.com',
	'role' => 'admin',
	'active' => true,
	'created_at' => '2026-01-02T03:04:05Z',
	'created_by' => 'root@example.com',
];

$api->queue(['admins' => [$admin]]);
$list = $repository->list();
Assert::same('GET', $api->calls[0]['method']);
Assert::same('/api/v1/admin/admins', $api->calls[0]['path']);
Assert::same('ops@example.com', $list[0]->email);
Assert::null($api->cookie);

$api->queue(['admins' => [$admin]]);
Assert::same('ops@example.com', $repository->get('ops@example.com')?->email);
$api->queue(['admins' => [$admin]]);
Assert::null($repository->get('otro@example.com'));

$session->setCookie('cookie-secreto');
$api->queue($admin);
$added = $repository->add('nuevo@example.com');
Assert::same('cookie-secreto', $api->cookie);
Assert::same('POST', $api->calls[3]['method']);
Assert::same('/api/v1/admin/admins', $api->calls[3]['path']);
Assert::same(['json' => ['email' => 'nuevo@example.com']], $api->calls[3]['options']);
Assert::same('ops@example.com', $added->email);

$api->queue(['email' => 'a+b@example.com', 'role' => 'admin', 'active' => false, 'created_at' => '2026-01-02T03:04:05Z']);
$toggled = $repository->setActive('a+b@example.com', false);
Assert::same('PATCH', $api->calls[4]['method']);
Assert::same('/api/v1/admin/admins/a%2Bb%40example.com', $api->calls[4]['path']);
Assert::same(['json' => ['active' => false]], $api->calls[4]['options']);
Assert::false($toggled->active);

$api->queue([]);
$repository->delete('a+b@example.com');
Assert::same('DELETE', $api->calls[5]['method']);
Assert::same('/api/v1/admin/admins/a%2Bb%40example.com', $api->calls[5]['path']);

$api->queue(['admins' => 'no-es-lista']);
Assert::exception(static fn () => $repository->list(), ApiError::class);

$api->queue(['admins' => ['no-es-array']]);
Assert::exception(static fn () => $repository->list(), ApiError::class);

$api->queue(['admins' => [['role' => 'admin']]]);
Assert::exception(static fn () => $repository->list(), ApiError::class);
