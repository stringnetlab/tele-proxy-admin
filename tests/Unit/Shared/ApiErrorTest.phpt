<?php declare(strict_types=1);

require __DIR__ . '/../../bootstrap.php';

use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiUnauthorizedException;
use GuzzleHttp\Psr7\Response;
use Tester\Assert;

$error = ApiError::fromResponse(new Response(
	400,
	[],
	json_encode([
		'error' => 'invalid_config',
		'message' => 'El campo es inválido.',
		'details' => ['field' => 'upstream_timeout_secs'],
	], JSON_THROW_ON_ERROR),
));
Assert::same(400, $error->status);
Assert::same('invalid_config', $error->errorCode);
Assert::same('El campo es inválido.', $error->getMessage());
Assert::same('upstream_timeout_secs', $error->field());

$unauthorized = ApiError::fromResponse(new Response(401, [], '{}'));
Assert::type(ApiUnauthorizedException::class, $unauthorized);
Assert::same('La sesión ha caducado. Inicia sesión de nuevo.', $unauthorized->getMessage());

$limited = ApiError::fromResponse(new Response(429, ['Retry-After' => '120'], '{}'));
Assert::same(120, $limited->retryAfter);

$opaque = ApiError::fromResponse(new Response(503, [], 'no-json'));
Assert::same('api_error', $opaque->errorCode);
Assert::same('No se pudo contactar con el servicio de administración.', $opaque->getMessage());
Assert::null($opaque->field());

Assert::same('La petición no es válida.', ApiError::defaultMessage(400));
Assert::same('No tienes permiso para realizar esta acción.', ApiError::defaultMessage(403));
Assert::same('El recurso no existe.', ApiError::defaultMessage(404));
Assert::same('La configuración cambió mientras editabas. Recarga y vuelve a intentarlo.', ApiError::defaultMessage(409));
