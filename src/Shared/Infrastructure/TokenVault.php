<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;


/**
 * Guarda tokens de un solo uso fuera de la sesión y de la URL: el navegador solo recibe un
 * identificador aleatorio que se canjea una vez mediante GETDEL.
 */
final class TokenVault
{
	private const TTL_SECONDS = 300;

	private const PREFIX = 'admin_ui_token:';

	private const HANDLE_PATTERN = '/^[a-f0-9]{64}$/';


	public function __construct(
		private readonly ValkeyConnection $valkey,
	) {}


	/**
	 * @param array<string, mixed> $payload
	 */
	public function store(array $payload): string
	{
		$handle = bin2hex(random_bytes(32));
		$this->valkey->set(
			self::PREFIX . $handle,
			json_encode($payload, JSON_THROW_ON_ERROR),
			self::TTL_SECONDS,
		);

		return $handle;
	}


	/**
	 * @return array<string, mixed>|null
	 */
	public function consume(string $handle): ?array
	{
		if (preg_match(self::HANDLE_PATTERN, $handle) !== 1) {
			return null;
		}

		$raw = $this->valkey->getDel(self::PREFIX . $handle);
		if ($raw === null) {
			return null;
		}

		$payload = json_decode($raw, true);

		return is_array($payload) ? $payload : null;
	}
}
