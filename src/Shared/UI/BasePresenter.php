<?php declare(strict_types=1);

namespace App\Shared\UI;

use Nette\Application\UI\Presenter;
use Nette\Http\Session;


abstract class BasePresenter extends Presenter
{
	/** @inject */
	public Session $session;

	private ?string $adminEmail = null;
	private ?string $adminRole = null;
	private ?string $sessionId = null;


	protected function startup(): void
	{
		parent::startup();
		$this->loadAdminFromSession();
	}


	private function loadAdminFromSession(): void
	{
		$section = $this->session->getSection('admin');
		$this->adminEmail = $section->get('email');
		$this->adminRole = $section->get('role');
		$this->sessionId = $section->get('sessionId');
	}


	protected function requireAdmin(): void
	{
		if ($this->adminEmail === null) {
			$this->flashMessage('Debes iniciar sesión para acceder a esta página', 'danger');
			$this->redirect(':Authentication:Login:default');
		}
	}


	public function isAdmin(): bool
	{
		return $this->adminEmail !== null;
	}


	public function getAdminEmail(): string
	{
		return $this->adminEmail ?? '';
	}


	protected function getSessionId(): string
	{
		return $this->sessionId ?? '';
	}


	public function setAdminSession(string $sessionId, string $email, string $role): void
	{
		$section = $this->session->getSection('admin');
		$section->set('sessionId', $sessionId);
		$section->set('email', $email);
		$section->set('role', $role);
		$section->set('loggedInAt', time());
	}


	public function clearAdminSession(): void
	{
		$section = $this->session->getSection('admin');
		$section->remove('sessionId');
		$section->remove('email');
		$section->remove('role');
		$section->remove('loggedInAt');
	}
}
