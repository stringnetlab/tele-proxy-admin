<?php declare(strict_types=1);

namespace App\ClientManagement\Domain\ValueObjects;

use InvalidArgumentException;


final class Preview
{
	public const MAX_TTL_SECONDS = 2_678_400;

	public const MAX_RENDER_TIMEOUT_MS = 120_000;

	public const MAX_BODY_BYTES = 10_485_760;

	public const MAX_VIEWPORT_SIDE = 4096;

	public const DEFAULT_TTL_SECONDS = 86_400;

	public const DEFAULT_TTL_MAX_SECONDS = 2_678_400;

	public const DEFAULT_RENDER_TIMEOUT_MS = 45_000;

	public const DEFAULT_BODY_BYTES = 2_097_152;

	public const DEFAULT_RATE_LIMIT_REQUESTS = 60;

	public const DEFAULT_RATE_LIMIT_WINDOW_SECONDS = 60;


	public function __construct(
		public readonly bool $enabled,
		public readonly bool $metaEnabled,
		public readonly bool $capabilityMeta,
		public readonly bool $capabilityContent,
		public readonly bool $capabilityScreenshot,
		public readonly bool $capabilityPdf,
		public readonly RateLimit $rateLimit,
		public readonly int $ttlDefaultSeconds,
		public readonly int $ttlMaxSeconds,
		public readonly int $maxRenderTimeoutMs,
		public readonly int $maxBodyBytes,
		public readonly bool $screenshotMaxFullPage,
		public readonly int $viewportWidth,
		public readonly int $viewportHeight,
	)
	{
		if ($this->ttlDefaultSeconds < 1 || $this->ttlDefaultSeconds > $this->ttlMaxSeconds) {
			throw new InvalidArgumentException(
				'preview.ttl_default_seconds debe estar entre 1 y ttl_max_seconds.',
			);
		}

		if ($this->ttlMaxSeconds > self::MAX_TTL_SECONDS) {
			throw new InvalidArgumentException(
				'preview.ttl_max_seconds no puede superar ' . self::MAX_TTL_SECONDS . '.',
			);
		}

		if ($this->maxRenderTimeoutMs < 1 || $this->maxRenderTimeoutMs > self::MAX_RENDER_TIMEOUT_MS) {
			throw new InvalidArgumentException(
				'preview.max_render_timeout_ms debe estar entre 1 y ' . self::MAX_RENDER_TIMEOUT_MS . '.',
			);
		}

		if ($this->maxBodyBytes < 1 || $this->maxBodyBytes > self::MAX_BODY_BYTES) {
			throw new InvalidArgumentException(
				'preview.max_body_bytes debe estar entre 1 y ' . self::MAX_BODY_BYTES . '.',
			);
		}

		foreach ([$this->viewportWidth, $this->viewportHeight] as $side) {
			if ($side < 1 || $side > self::MAX_VIEWPORT_SIDE) {
				throw new InvalidArgumentException(
					'preview.screenshot.default_viewport: cada lado debe estar en 1..' . self::MAX_VIEWPORT_SIDE . '.',
				);
			}
		}
	}


	/**
	 * @param array<string, mixed> $data
	 */
	public static function fromApiResponse(array $data): self
	{
		$capabilities = is_array($data['capabilities'] ?? null) ? $data['capabilities'] : [];
		$rateLimit = is_array($data['rate_limit'] ?? null) ? $data['rate_limit'] : [];
		$screenshot = is_array($data['screenshot'] ?? null) ? $data['screenshot'] : [];
		$viewport = is_array($screenshot['default_viewport'] ?? null) ? $screenshot['default_viewport'] : [1920, 1080];

		return new self(
			enabled: (bool) ($data['enabled'] ?? false),
			metaEnabled: (bool) ($data['meta_enabled'] ?? true),
			capabilityMeta: (bool) ($capabilities['meta'] ?? true),
			capabilityContent: (bool) ($capabilities['content'] ?? true),
			capabilityScreenshot: (bool) ($capabilities['screenshot'] ?? false),
			capabilityPdf: (bool) ($capabilities['pdf'] ?? false),
			rateLimit: new RateLimit(
				(int) ($rateLimit['max_requests'] ?? self::DEFAULT_RATE_LIMIT_REQUESTS),
				(int) ($rateLimit['window_seconds'] ?? self::DEFAULT_RATE_LIMIT_WINDOW_SECONDS),
			),
			ttlDefaultSeconds: (int) ($data['ttl_default_seconds'] ?? self::DEFAULT_TTL_SECONDS),
			ttlMaxSeconds: (int) ($data['ttl_max_seconds'] ?? self::DEFAULT_TTL_MAX_SECONDS),
			maxRenderTimeoutMs: (int) ($data['max_render_timeout_ms'] ?? self::DEFAULT_RENDER_TIMEOUT_MS),
			maxBodyBytes: (int) ($data['max_body_bytes'] ?? self::DEFAULT_BODY_BYTES),
			screenshotMaxFullPage: (bool) ($screenshot['max_full_page'] ?? true),
			viewportWidth: (int) ($viewport[0] ?? 1920),
			viewportHeight: (int) ($viewport[1] ?? 1080),
		);
	}


	/**
	 * @return array<string, mixed>
	 */
	public function toArray(): array
	{
		return [
			'enabled' => $this->enabled,
			'meta_enabled' => $this->metaEnabled,
			'capabilities' => [
				'meta' => $this->capabilityMeta,
				'content' => $this->capabilityContent,
				'screenshot' => $this->capabilityScreenshot,
				'pdf' => $this->capabilityPdf,
			],
			'rate_limit' => $this->rateLimit->toArray(),
			'ttl_default_seconds' => $this->ttlDefaultSeconds,
			'ttl_max_seconds' => $this->ttlMaxSeconds,
			'max_render_timeout_ms' => $this->maxRenderTimeoutMs,
			'max_body_bytes' => $this->maxBodyBytes,
			'screenshot' => [
				'max_full_page' => $this->screenshotMaxFullPage,
				'default_viewport' => [$this->viewportWidth, $this->viewportHeight],
			],
		];
	}
}
