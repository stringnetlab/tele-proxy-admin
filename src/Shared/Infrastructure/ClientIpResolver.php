<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;

use Nette\Http\IRequest;


final class ClientIpResolver
{
	/** @var list<string> */
	private readonly array $trustedProxies;


	public function __construct(string $trustedProxies)
	{
		$this->trustedProxies = array_values(array_filter(
			array_map('trim', explode(',', $trustedProxies)),
			static fn (string $proxy): bool => $proxy !== '',
		));
	}


	public function resolve(IRequest $request): string
	{
		$remote = $request->getRemoteAddress();
		if ($remote === null) {
			return 'unknown';
		}

		if (!$this->isTrusted($remote)) {
			return $remote;
		}

		$cloudflare = $request->getHeader('CF-Connecting-IP');
		if (is_string($cloudflare) && filter_var($cloudflare, FILTER_VALIDATE_IP) !== false) {
			return $cloudflare;
		}

		$forwarded = $request->getHeader('X-Forwarded-For');
		if (is_string($forwarded)) {
			foreach (array_reverse(array_map('trim', explode(',', $forwarded))) as $candidate) {
				if (filter_var($candidate, FILTER_VALIDATE_IP) !== false && !$this->isTrusted($candidate)) {
					return $candidate;
				}
			}
		}

		return $remote;
	}


	private function isTrusted(string $ip): bool
	{
		foreach ($this->trustedProxies as $proxy) {
			if ($this->matches($ip, $proxy)) {
				return true;
			}
		}

		return false;
	}


	private function matches(string $ip, string $cidr): bool
	{
		if (!str_contains($cidr, '/')) {
			return $ip === $cidr;
		}

		[$subnet, $bits] = explode('/', $cidr, 2);
		if (!ctype_digit($bits)) {
			return false;
		}

		$ipBin = @inet_pton($ip);
		$subnetBin = @inet_pton($subnet);
		if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
			return false;
		}

		$bits = (int) $bits;
		$maxBits = strlen($ipBin) * 8;
		if ($bits < 0 || $bits > $maxBits) {
			return false;
		}

		$bytes = intdiv($bits, 8);
		$remainder = $bits % 8;

		if ($bytes > 0 && substr($ipBin, 0, $bytes) !== substr($subnetBin, 0, $bytes)) {
			return false;
		}

		if ($remainder === 0) {
			return true;
		}

		$mask = (0xFF << (8 - $remainder)) & 0xFF;

		return (ord($ipBin[$bytes]) & $mask) === (ord($subnetBin[$bytes]) & $mask);
	}
}
