<?php declare(strict_types=1);

namespace App\AdminManagement\Domain;

use DateTimeImmutable;
use InvalidArgumentException;
use Throwable;


final class AdminUser
{
	public function __construct(
		public readonly string $email,
		public readonly string $role,
		public readonly bool $active,
		public readonly DateTimeImmutable $createdAt,
		public readonly string $createdBy,
	) {}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		$email = $data['email'] ?? null;
		if (!is_string($email) || $email === '') {
			throw new InvalidArgumentException('Falta el email del administrador.');
		}

		$createdAt = $data['created_at'] ?? '';
		try {
			$createdDate = new DateTimeImmutable(is_string($createdAt) && $createdAt !== '' ? $createdAt : 'now');
		} catch (Throwable $e) {
			throw new InvalidArgumentException('created_at no es una fecha válida.', 0, $e);
		}

		return new self(
			email: $email,
			role: is_string($data['role'] ?? null) ? $data['role'] : 'admin',
			active: (bool) ($data['active'] ?? true),
			createdAt: $createdDate,
			createdBy: is_string($data['created_by'] ?? null) ? $data['created_by'] : '',
		);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'email' => $this->email,
			'role' => $this->role,
			'active' => $this->active,
			'created_at' => $this->createdAt->format('c'),
			'created_by' => $this->createdBy,
		];
	}
}
