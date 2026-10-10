<?php declare(strict_types=1);

namespace App\Authentication\UI;

use App\Authentication\Application\LoginWithGoogleHandler;
use App\Authentication\Application\LoginWithMasterTokenHandler;
use App\Authentication\Application\LogoutHandler;
use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Shared\UI\BasePresenter;
use Nette\Application\UI\Form;


final class LoginPresenter extends BasePresenter
{
	public function __construct(
		private readonly LoginWithGoogleHandler $googleHandler,
		private readonly LoginWithMasterTokenHandler $masterTokenHandler,
		private readonly LogoutHandler $logoutHandler,
		private readonly ApiAuthAdapter $authAdapter,
	) {}


	public function actionDefault(): void
	{
		if ($this->isAdmin()) {
			$this->redirect(':ClientManagement:Client:list');
		}
	}


	public function actionGoogleUrl(): void
	{
		try {
			$url = $this->authAdapter->getGoogleAuthUrl();
			$this->redirectUrl($url);
		} catch (\Throwable) {
			$this->flashMessage('Google OAuth no está disponible', 'danger');
			$this->redirect('default');
		}
	}


	public function actionGoogleCallback(string $code): void
	{
		$ip = $this->getHttpRequest()->getRemoteAddress() ?? 'unknown';
		$ua = $this->getHttpRequest()->getHeader('User-Agent') ?? 'unknown';
		$session = $this->googleHandler->handleCallback($code, $ip, $ua);

		if ($session === null) {
			$this->flashMessage('Autenticación fallida', 'danger');
			$this->redirect('default');
		}

		$this->setAdminSession($session->sessionId, $session->email, $session->role);
		$this->redirect(':ClientManagement:Client:list');
	}


	public function actionLogout(): void
	{
		if ($this->isAdmin()) {
			$this->logoutHandler->handle($this->getAdminEmail(), $this->getSessionId());
		}
		$this->clearAdminSession();
		$this->redirect('default');
	}


	protected function createComponentMasterTokenForm(): Form
	{
		$form = new Form;
		$form->addPassword('token', 'Master Token')
			->setRequired('Ingresa el master token');
		$form->addSubmit('login', 'Iniciar sesión');
		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$ip = $this->getHttpRequest()->getRemoteAddress() ?? 'unknown';
			$ua = $this->getHttpRequest()->getHeader('User-Agent') ?? 'unknown';
			$session = $this->masterTokenHandler->handle($values->token, $ip, $ua);

			if ($session === null) {
				$this->flashMessage('Master token inválido', 'danger');
				return;
			}

			$this->setAdminSession($session->sessionId, $session->email, $session->role);
			$this->redirect(':Client:list');
		};
		return $form;
	}
}
