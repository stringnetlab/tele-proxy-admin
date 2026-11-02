<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\AdminManagement\Domain\AdminUser;
use Tester\Assert;

$admin = AdminUser::fromApiResponse([
	'email' => 'ops@example.com',
	'role' => 'admin',
	'active' => false,
	'created_at' => '2026-01-02T03:04:05Z',
	'created_by' => 'root@example.com',
]);

Assert::same('ops@example.com', $admin->email);
Assert::same('admin', $admin->role);
Assert::false($admin->active);
Assert::same('2026-01-02T03:04:05+00:00', $admin->createdAt->format('c'));
Assert::same('root@example.com', $admin->createdBy);

$defaults = AdminUser::fromApiResponse(['email' => 'ops@example.com']);
Assert::same('admin', $defaults->role);
Assert::true($defaults->active);
Assert::same('', $defaults->createdBy);

$serialized = $admin->toArray();
Assert::same('ops@example.com', $serialized['email']);
Assert::same('2026-01-02T03:04:05+00:00', $serialized['created_at']);

Assert::exception(
	static fn () => AdminUser::fromApiResponse([]),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => AdminUser::fromApiResponse(['email' => '']),
	InvalidArgumentException::class,
);
Assert::exception(
	static fn () => AdminUser::fromApiResponse(['email' => 'ops@example.com', 'created_at' => 'no-es-fecha']),
	InvalidArgumentException::class,
);
