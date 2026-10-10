<?php declare(strict_types=1);

namespace App\ClientManagement\Domain\ValueObjects;

use InvalidArgumentException;


final class Whitelist
{
	private const MAX_ENTRIES = 100;
	private const DOMAIN_PATTERN = '/^(\*\.)?([a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/';


	/** @var string[] */
	private readonly array $domains;


	/**
	 * @param string[] $domains
	 */
	public function __construct(array $domains)
	{
		$this->validate($domains);
		$this->domains = array_values(array_unique($domains));
	}


	/**
	 * @param string[] $domains
	 */
	private function validate(array $domains): void
	{
		if (count($domains) > self::MAX_ENTRIES) {
			throw new InvalidArgumentException(
				'La whitelist no puede tener mas de ' . self::MAX_ENTRIES . ' entradas',
			);
		}

		foreach ($domains as $domain) {
			$domain = trim($domain);
			if ($domain === '') {
				continue;
			}

			if (!preg_match(self::DOMAIN_PATTERN, $domain)) {
				throw new InvalidArgumentException(
					"Dominio invalido en whitelist: '$domain'. Solo se permiten dominios o wildcards de dominios (*.ejemplo.com)",
				);
			}

			if (filter_var($domain, FILTER_VALIDATE_IP)) {
				throw new InvalidArgumentException(
					"Dominio invalido en whitelist: '$domain'. No se permiten direcciones IP",
				);
			}
		}
	}


	/**
	 * @return string[]
	 */
	public function getDomains(): array
	{
		return $this->domains;
	}


	public function count(): int
	{
		return count($this->domains);
	}


	public function isEmpty(): bool
	{
		return count($this->domains) === 0;
	}
}
