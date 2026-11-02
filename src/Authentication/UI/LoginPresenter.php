<?php declare(strict_types=1);

namespace App\Authentication\UI;

use App\Authentication\Application\LoginRateLimiter;
use App\Authentication\Application\LoginWithGoogleHandler;
use App\Authentication\Domain\AuthenticationFailedException;
use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ClientIpResolver;
use App\Shared\Infrastructure\RateLimitExceededException;
use App\Shared\UI\BasePresenter;
use Nette\Application\BadRequestException;


final class LoginPresenter extends BasePresenter
{
	public function __construct(
		private readonly LoginWithGoogleHandler $googleHandler,
		private readonly LoginRateLimiter $loginRateLimiter,
		private readonly ApiAuthAdapter $apiAuthAdapter,
		private readonly ClientIpResolver $clientIpResolver,
	) {}


	protected function requiresAuth(): bool
	{
		return false;
	}


	public function actionDefault(): void
	{
		if ($this->isAdmin()) {
			$this->redirect(':ClientManagement:Client:list');
		}
	}


	public function actionGoogleUrl(): void
	{
		try {
			$this->loginRateLimiter->assertAllowed($this->getHttpRequest(), 'google_url');
			$url = $this->apiAuthAdapter->getGoogleAuthUrl();
		} catch (RateLimitExceededException|ApiError $e) {
			$this->flashMessage($e->getMessage(), 'danger');
			$this->redirect('default');
		}

		$this->redirectUrl($url);
	}


	public function actionGoogleCallback(
		string $code = '',
		string $state = '',
		?string $error = null,
	): void
	{
		if ($error !== null) {
			$this->flashMessage('Cancelaste el inicio de sesión con Google.', 'danger');
			$this->redirect('default');
		}

		if ($code === '' || $state === '') {
			$this->flashMessage('La respuesta de Google llegó incompleta. Inténtalo de nuevo.', 'danger');
			$this->redirect('default');
		}

		$request = $this->getHttpRequest();

		try {
			$this->loginRateLimiter->assertAllowed($request, 'callback');
			$session = $this->googleHandler->handle(
				$code,
				$state,
				$this->clientIpResolver->resolve($request),
				$request->getHeader('User-Agent') ?? '',
			);
		} catch (RateLimitExceededException|AuthenticationFailedException|ApiError $e) {
			$this->flashMessage($e->getMessage(), 'danger');
			$this->redirect('default');
		}

		$this->setAdminSession($session);
		$this->redirect(':ClientManagement:Client:list');
	}


	public function actionLogout(): void
	{
		if (!$this->getHttpRequest()->isMethod('POST')) {
			throw new BadRequestException('Método no permitido.', 405);
		}

		$this->performLogout();
		$this->redirect('default');
	}
}
