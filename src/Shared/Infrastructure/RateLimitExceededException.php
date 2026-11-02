<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;


final class RateLimitExceededException extends \RuntimeException
{
	public function __construct(
		public readonly int $retryAfter = 0,
	)
	{
		parent::__construct('Demasiadas solicitudes. Espera unos minutos.');
	}
}
