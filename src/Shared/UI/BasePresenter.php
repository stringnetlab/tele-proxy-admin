<?php declare(strict_types=1);

namespace App\Shared\UI;

use App\Authentication\Application\LogoutHandler;
use App\Authentication\Domain\Session as AdminSession;
use App\Authentication\Infrastructure\ApiAuthAdapter;
use App\Shared\Infrastructure\ApiCookieCipher;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiSession;
use App\Shared\Infrastructure\ApiUnauthorizedException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Session;


abstract class BasePresenter extends Presenter
{
	private const SESSION_SECTION = 'admin';


	/** @inject */
	public Session $session;

	/** @inject */
	public ApiAuthAdapter $authAdapter;

	/** @inject */
	public ApiCookieCipher $cookieCipher;

	/** @inject */
	public LogoutHandler $logoutHandler;

	/** @inject */
	public ApiSession $apiSession;

	private ?AdminSession $adminSession = null;


	protected function startup(): void
	{
		parent::startup();
		$this->setLayout(dirname(__DIR__, 3) . '/app/Presentation/templates/@layout.latte');
		$this->adminSession = $this->loadAdminSession();
		$this->apiSession->setCookie($this->adminSession?->apiCookie);

		if ($this->requiresAuth()) {
			$this->requireAdmin();
		}
	}


	protected function requiresAuth(): bool
	{
		return true;
	}


	protected function requireAdmin(): void
	{
		$session = $this->adminSession;
		if ($session === null) {
			$this->redirect(':Authentication:Login:default');
		}

		if ($session->isExpired(time())) {
			$this->expireAdminSession('Tu sesión expiró. Inicia sesión de nuevo.');
			return;
		}

		try {
			$me = $this->authAdapter->getMe($session->apiCookie);
		} catch (ApiUnauthorizedException) {
			$this->expireAdminSession('Tu sesión del panel expiró. Inicia sesión de nuevo.');
			return;
		} catch (ApiError $e) {
			throw new BadRequestException('No se pudo validar la sesión con el API de control.', 503, $e);
		}

		$email = $me['email'] ?? null;
		$role = $me['role'] ?? null;
		if (!is_string($email) || $email !== $session->email || $role !== 'admin') {
			$this->expireAdminSession('Tu cuenta ya no tiene permisos de administrador.');
		}
	}


	public function isAdmin(): bool
	{
		return $this->adminSession !== null;
	}


	public function getAdminEmail(): string
	{
		return $this->adminSession === null ? '' : $this->adminSession->email;
	}


	public function getApiCookie(): ?string
	{
		return $this->adminSession?->apiCookie;
	}


	public function setAdminSession(AdminSession $session): void
	{
		$section = $this->session->getSection(self::SESSION_SECTION);
		$section->set('email', $session->email);
		$section->set('role', $session->role);
		$section->set('sessionId', $session->netteSessionId);
		$section->set('expiresAt', $session->expiresAt);
		$section->set('ip', $session->ip);
		$section->set('userAgent', $session->userAgent);
		$section->set('apiCookie', $this->cookieCipher->encrypt($session->apiCookie));

		$this->adminSession = $session;
	}


	public function clearAdminSession(): void
	{
		$this->session->getSection(self::SESSION_SECTION)->remove();
		$this->adminSession = null;
	}


	protected function reportApiError(ApiError $error): never
	{
		if ($error instanceof ApiUnauthorizedException) {
			$this->expireAdminSession('Tu sesión del panel expiró. Inicia sesión de nuevo.');
		}

		$status = $error->status >= 400 && $error->status <= 599 ? $error->status : 502;

		throw new BadRequestException($error->getMessage(), $status, $error);
	}


	protected function createComponentLogoutForm(): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addSubmit('logout', 'Cerrar sesión');
		$form->onSuccess[] = function (Form $form): void {
			$this->performLogout();
			$this->redirect(':Authentication:Login:default');
		};

		return $form;
	}


	protected function performLogout(): void
	{
		$session = $this->adminSession;
		if ($session !== null) {
			$this->logoutHandler->handle($session->email, $session->netteSessionId, $session->apiCookie);
		}

		$this->clearAdminSession();
		$this->session->destroy();
	}


	private function loadAdminSession(): ?AdminSession
	{
		$section = $this->session->getSection(self::SESSION_SECTION);

		$email = $section->get('email');
		$role = $section->get('role');
		$sessionId = $section->get('sessionId');
		$expiresAt = $section->get('expiresAt');
		$encryptedCookie = $section->get('apiCookie');

		if (!is_string($email) || !is_string($role) || !is_string($sessionId) || !is_int($expiresAt) || !is_string($encryptedCookie)) {
			return null;
		}

		$apiCookie = $this->cookieCipher->decrypt($encryptedCookie);
		if ($apiCookie === null) {
			return null;
		}

		$ip = $section->get('ip');
		$userAgent = $section->get('userAgent');

		return new AdminSession(
			netteSessionId: $sessionId,
			email: $email,
			role: $role,
			apiCookie: $apiCookie,
			expiresAt: $expiresAt,
			ip: is_string($ip) ? $ip : '',
			userAgent: is_string($userAgent) ? $userAgent : '',
		);
	}


	private function expireAdminSession(string $message): void
	{
		$this->clearAdminSession();
		$this->flashMessage($message, 'danger');
		$this->redirect(':Authentication:Login:default');
	}
}
