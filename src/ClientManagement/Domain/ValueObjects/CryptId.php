<?php declare(strict_types=1);

namespace App\ClientManagement\Domain\ValueObjects;

use InvalidArgumentException;


final class CryptId
{
	private const LENGTH = 12;
	private const PATTERN = '/^[A-Za-z0-9_-]{12}$/';


	public function __construct(
		public readonly string $value,
	)
	{
		if (!preg_match(self::PATTERN, $value)) {
			throw new InvalidArgumentException(
				"crypt_id invalido: debe ser un nanoid de " . self::LENGTH . " caracteres URL-safe",
			);
		}
	}


	public function equals(self $other): bool
	{
		return $this->value === $other->value;
	}


	public function __toString(): string
	{
		return $this->value;
	}
}
