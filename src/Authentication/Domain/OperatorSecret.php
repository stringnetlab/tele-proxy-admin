<?php declare(strict_types=1);

namespace App\Authentication\Domain;


final class OperatorSecret
{
	public function __construct(
		public readonly string $token,
	)
	{
	}


	public function isMasterToken(): bool
	{
		return $this->token !== '';
	}
}
