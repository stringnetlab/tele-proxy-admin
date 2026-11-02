<?php declare(strict_types=1);

namespace App\ClientManagement\Domain\ValueObjects;

use InvalidArgumentException;


final class Scripting
{
	private const MAX_EXPRESSION_BYTES = 4096;


	public function __construct(
		public readonly bool $enabled,
		public readonly string $code,
		public readonly string $codeHash,
		public readonly string $expression,
	)
	{
		if (strlen($this->expression) > self::MAX_EXPRESSION_BYTES) {
			throw new InvalidArgumentException(
				'scripting.expression supera los ' . self::MAX_EXPRESSION_BYTES . ' bytes.',
			);
		}
	}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		$code = $data['scripting_code'] ?? null;
		$codeHash = $data['scripting_code_hash'] ?? null;
		$expression = $data['scripting_expression'] ?? null;

		return new self(
			enabled: (bool) ($data['scripting_enabled'] ?? false),
			code: is_string($code) ? $code : '',
			codeHash: is_string($codeHash) ? $codeHash : '',
			expression: is_string($expression) ? $expression : '',
		);
	}


	/**
	 * @return array{enabled: bool, code: string, code_hash: string, expression: string}
	 */
	public function toUpdatePayload(): array
	{
		return [
			'enabled' => $this->enabled,
			'code' => $this->code,
			'code_hash' => $this->code === '' ? $this->codeHash : self::hash($this->code),
			'expression' => $this->expression,
		];
	}


	public static function hash(string $code): string
	{
		return 'sha256:' . hash('sha256', $code);
	}
}
