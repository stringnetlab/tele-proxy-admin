<?php declare(strict_types=1);

namespace App\ClientManagement\Domain\ValueObjects;

use InvalidArgumentException;


/**
 * Entradas `host` o `host:puerto`. Sin comodines, sin esquema, sin rutas; IPs públicas.
 */
final class Whitelist
{
	private const MAX_ENTRIES = 100;

	private const MAX_HOST_BYTES = 253;

	private const MAX_LABEL_BYTES = 63;


	/** @var list<string> */
	private readonly array $entries;


	/**
	 * @param list<string> $entries
	 */
	public function __construct(array $entries)
	{
		$normalized = [];

		foreach ($entries as $entry) {
			$entry = trim($entry);
			if ($entry === '') {
				continue;
			}

			self::assertValid($entry);
			$normalized[] = $entry;
		}

		$normalized = array_values(array_unique($normalized));
		if (count($normalized) > self::MAX_ENTRIES) {
			throw new InvalidArgumentException(
				'La whitelist no puede tener más de ' . self::MAX_ENTRIES . ' entradas.',
			);
		}

		$this->entries = $normalized;
	}


	public static function fromLines(string $text): self
	{
		return new self(preg_split('/\R/', $text) ?: []);
	}


	public static function fromApiResponse(mixed $data): self
	{
		if (!is_array($data)) {
			return new self([]);
		}

		$entries = [];
		foreach ($data as $entry) {
			if (is_string($entry)) {
				$entries[] = $entry;
			}
		}

		return new self($entries);
	}


	/**
	 * @return list<string>
	 */
	public function entries(): array
	{
		return $this->entries;
	}


	public function toText(): string
	{
		return implode("\n", $this->entries);
	}


	private static function assertValid(string $entry): void
	{
		[$host] = self::split($entry);

		if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
			if (!self::isPublicIp($host)) {
				throw new InvalidArgumentException(
					"La entrada '$entry' apunta a una dirección privada o reservada.",
				);
			}

			return;
		}

		if (!self::isValidHostname($host)) {
			throw new InvalidArgumentException(
				"La entrada '$entry' no es un dominio válido ni un host:puerto.",
			);
		}
	}


	/**
	 * @return array{string, int|null}
	 */
	private static function split(string $entry): array
	{
		if (str_starts_with($entry, '[')) {
			$close = strpos($entry, ']');
			if ($close === false) {
				throw new InvalidArgumentException("La entrada '$entry' tiene corchetes desbalanceados.");
			}

			$host = substr($entry, 1, $close - 1);
			$tail = substr($entry, $close + 1);
			if ($host === '') {
				throw new InvalidArgumentException("La entrada '$entry' no tiene host.");
			}

			if ($tail === '') {
				return [$host, null];
			}

			if (!str_starts_with($tail, ':')) {
				throw new InvalidArgumentException("La entrada '$entry' no es un host:puerto válido.");
			}

			return [$host, self::parsePort($entry, substr($tail, 1))];
		}

		$colons = substr_count($entry, ':');

		if ($colons === 0) {
			return [$entry, null];
		}

		if ($colons === 1) {
			[$host, $port] = explode(':', $entry, 2);
			if ($host === '') {
				throw new InvalidArgumentException("La entrada '$entry' no tiene host.");
			}

			return [$host, self::parsePort($entry, $port)];
		}

		throw new InvalidArgumentException(
			"La entrada '$entry' tiene un IPv6 ambiguo; usa corchetes: [ipv6]:puerto.",
		);
	}


	private static function parsePort(string $entry, string $raw): int
	{
		if (!ctype_digit($raw)) {
			throw new InvalidArgumentException("La entrada '$entry' tiene un puerto inválido.");
		}

		$port = (int) $raw;
		if ($port < 1 || $port > 65535) {
			throw new InvalidArgumentException("La entrada '$entry' tiene un puerto fuera de 1..65535.");
		}

		return $port;
	}


	private static function isPublicIp(string $ip): bool
	{
		return filter_var(
			$ip,
			FILTER_VALIDATE_IP,
			FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
		) !== false;
	}


	private static function isValidHostname(string $host): bool
	{
		if ($host === '' || strlen($host) > self::MAX_HOST_BYTES) {
			return false;
		}

		$dot = strrpos($host, '.');
		if ($dot === false) {
			return false;
		}

		$prefix = substr($host, 0, $dot);
		$tld = substr($host, $dot + 1);

		if ($tld === '' || strlen($tld) > self::MAX_LABEL_BYTES) {
			return false;
		}

		if (!ctype_alpha($tld)) {
			return false;
		}

		foreach (explode('.', $prefix) as $label) {
			if ($label === '' || strlen($label) > self::MAX_LABEL_BYTES) {
				return false;
			}

			if (str_starts_with($label, '-') || str_ends_with($label, '-')) {
				return false;
			}

			if (!preg_match('/^[A-Za-z0-9-]+$/', $label)) {
				return false;
			}
		}

		return true;
	}
}
