<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Psr\Http\Message\ResponseInterface;
use Throwable;


class ApiError extends \RuntimeException
{
	public function __construct(
		public readonly int $status,
		public readonly string $errorCode,
		string $message,
		public readonly mixed $details = null,
		public readonly ?int $retryAfter = null,
		?Throwable $previous = null,
	)
	{
		parent::__construct($message, 0, $previous);
	}


	public static function fromResponse(ResponseInterface $response): self
	{
		$status = $response->getStatusCode();
		$decoded = json_decode((string) $response->getBody(), true);

		$errorCode = 'api_error';
		$message = self::defaultMessage($status);
		$details = null;

		if (is_array($decoded)) {
			if (isset($decoded['error']) && is_string($decoded['error'])) {
				$errorCode = $decoded['error'];
			}

			if (isset($decoded['message']) && is_string($decoded['message']) && $decoded['message'] !== '') {
				$message = $decoded['message'];
			}

			if (array_key_exists('details', $decoded)) {
				$details = $decoded['details'];
			}
		}

		$retryAfter = self::retryAfter($response);

		return $status === 401
			? new ApiUnauthorizedException($status, $errorCode, $message, $details, $retryAfter)
			: new self($status, $errorCode, $message, $details, $retryAfter);
	}


	public static function defaultMessage(int $status): string
	{
		return match (true) {
			$status === 0 => 'No se pudo contactar con el servicio de administración.',
			$status === 400 => 'La petición no es válida.',
			$status === 401 => 'La sesión ha caducado. Inicia sesión de nuevo.',
			$status === 403 => 'No tienes permiso para realizar esta acción.',
			$status === 404 => 'El recurso no existe.',
			$status === 409 => 'La configuración cambió mientras editabas. Recarga y vuelve a intentarlo.',
			$status === 429 => 'Demasiadas solicitudes. Espera unos minutos.',
			$status >= 500 => 'No se pudo contactar con el servicio de administración.',
			default => 'Error inesperado del servicio de administración.',
		};
	}


	public function field(): ?string
	{
		if (is_array($this->details)) {
			$field = $this->details['field'] ?? null;
			if (is_string($field)) {
				return $field;
			}
		}

		return null;
	}


	private static function retryAfter(ResponseInterface $response): ?int
	{
		$header = trim($response->getHeaderLine('Retry-After'));
		if ($header === '') {
			return null;
		}

		if (ctype_digit($header)) {
			return (int) $header;
		}

		$timestamp = strtotime($header);

		return $timestamp === false ? null : max(0, $timestamp - time());
	}
}
