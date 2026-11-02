<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;


final class ApiCookieCipher
{
	private const NONCE_BYTES = 12;

	private const TAG_BYTES = 16;

	private readonly string $key;


	public function __construct(string $secret)
	{
		$this->key = hash('sha256', $secret, true);
	}


	public function encrypt(string $plain): string
	{
		$nonce = random_bytes(self::NONCE_BYTES);
		$tag = '';

		$ciphertext = openssl_encrypt(
			$plain,
			'aes-256-gcm',
			$this->key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag,
		);

		if ($ciphertext === false) {
			throw new \RuntimeException('No se pudo cifrar la cookie de sesión del API.');
		}

		return base64_encode($nonce . $tag . $ciphertext);
	}


	public function decrypt(string $payload): ?string
	{
		$raw = base64_decode($payload, true);
		if ($raw === false || strlen($raw) < self::NONCE_BYTES + self::TAG_BYTES) {
			return null;
		}

		$nonce = substr($raw, 0, self::NONCE_BYTES);
		$tag = substr($raw, self::NONCE_BYTES, self::TAG_BYTES);
		$ciphertext = substr($raw, self::NONCE_BYTES + self::TAG_BYTES);

		$plain = openssl_decrypt(
			$ciphertext,
			'aes-256-gcm',
			$this->key,
			OPENSSL_RAW_DATA,
			$nonce,
			$tag,
		);

		return $plain === false ? null : $plain;
	}
}
