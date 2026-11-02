<?php declare(strict_types=1);

namespace App\Authentication\Application;

use App\Shared\Infrastructure\ClientIpResolver;
use App\Shared\Infrastructure\RateLimitExceededException;
use App\Shared\Infrastructure\RateLimiter;
use Nette\Http\IRequest;


final class LoginRateLimiter
{
	public function __construct(
		private readonly RateLimiter $limiter,
		private readonly ClientIpResolver $clientIpResolver,
		private readonly string $maxRequests,
		private readonly string $windowSeconds,
	)
	{
	}


	public function assertAllowed(IRequest $request, string $scope): void
	{
		$key = 'admin_login:' . $scope . ':' . $this->clientIpResolver->resolve($request);

		if ($this->limiter->isRateLimited($key, (int) $this->maxRequests, (int) $this->windowSeconds)) {
			throw new RateLimitExceededException($this->limiter->getRetryAfter($key));
		}
	}
}
