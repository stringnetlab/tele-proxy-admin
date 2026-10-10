<?php declare(strict_types=1);

namespace App\AdminManagement\Domain;


final class AdminUser
{
	public function __construct(
		public readonly string $email,
		public readonly string $role,
		public readonly bool $active,
		public readonly \DateTimeImmutable $createdAt,
		public readonly string $createdBy,
	)
	{
	}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		return new self(
			email: $data['email'] ?? '',
			role: $data['role'] ?? 'admin',
			active: (bool) ($data['active'] ?? true),
			createdAt: new \DateTimeImmutable($data['created_at'] ?? 'now'),
			createdBy: $data['created_by'] ?? '',
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
