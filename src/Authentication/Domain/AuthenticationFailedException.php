<?php declare(strict_types=1);

namespace App\Authentication\Domain;


final class AuthenticationFailedException extends \RuntimeException
{
	public function __construct(
		public readonly int $status,
		string $message,
		public readonly ?int $retryAfter = null,
		?\Throwable $previous = null,
	)
	{
		parent::__construct($message, 0, $previous);
	}


	public static function fromCallback(GoogleCallbackResult $result): self
	{
		$message = match (true) {
			$result->status === 400 => 'La sesión de login expiró. Inténtalo de nuevo.',
			$result->status === 401 => 'Google no pudo verificar la cuenta. Inténtalo de nuevo.',
			$result->status === 403 => 'Esta cuenta de Google no está autorizada como administrador. Pide a un administrador que te añada.',
			$result->status === 429 => 'Demasiados intentos de login. Espera unos minutos.',
			$result->status === 503 => 'El login con Google no está configurado en el servidor.',
			$result->status >= 500 => 'No se pudo contactar con el servicio de administración.',
			default => 'No se pudo completar el inicio de sesión.',
		};

		return new self($result->status, $message, $result->retryAfter);
	}
}
