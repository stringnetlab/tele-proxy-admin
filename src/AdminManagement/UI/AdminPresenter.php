<?php declare(strict_types=1);

namespace App\AdminManagement\UI;

use App\AdminManagement\Application\AddAdminHandler;
use App\AdminManagement\Application\DeleteAdminHandler;
use App\AdminManagement\Application\GetAdminHandler;
use App\AdminManagement\Application\ListAdminsHandler;
use App\AdminManagement\Application\ToggleAdminHandler;
use App\AdminManagement\Domain\AdminUser;
use App\Shared\Infrastructure\ApiError;
use App\Shared\UI\BasePresenter;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\ArrayHash;


final class AdminPresenter extends BasePresenter
{
	private ?AdminUser $admin = null;


	public function __construct(
		private readonly ListAdminsHandler $listHandler,
		private readonly GetAdminHandler $getHandler,
		private readonly AddAdminHandler $addHandler,
		private readonly ToggleAdminHandler $toggleHandler,
		private readonly DeleteAdminHandler $deleteHandler,
	) {}


	public function actionList(): void
	{
		try {
			$admins = $this->listHandler->handle();
		} catch (ApiError $e) {
			$this->reportApiError($e);
		}

		$this->template->admins = $admins;
		$this->template->currentEmail = $this->getAdminEmail();
	}


	public function actionDetail(string $email): void
	{
		$this->admin = $this->loadAdmin($email);
		$this->template->admin = $this->admin;
		$this->template->currentEmail = $this->getAdminEmail();
	}


	public function actionCreate(): void
	{
	}


	protected function createComponentAdminForm(): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addEmail('email', 'Email')
			->setRequired('El email es obligatorio.');
		$form->addSubmit('save', 'Añadir administrador');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$email = $values['email'] ?? null;
			$email = is_string($email) ? trim($email) : '';

			try {
				$admin = $this->addHandler->handle($email);
			} catch (ApiError $e) {
				$this->showApiError($form, $e);
				return;
			}

			$this->flashMessage('Administrador añadido: ' . $admin->email, 'success');
			$this->redirect('list');
		};

		return $form;
	}


	protected function createComponentActionsForm(): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addSubmit('toggle', $this->admin !== null && $this->admin->active ? 'Desactivar' : 'Activar');
		$form['toggle']->setHtmlAttribute('class', 'btn btn-warning');
		$form->addSubmit('delete', 'Eliminar');
		$form['delete']->setHtmlAttribute('class', 'btn btn-danger');
		$form['delete']->setHtmlAttribute('data-confirm', '¿Eliminar este administrador? Esta acción no se puede deshacer.');

		$form->onSuccess[] = function (Form $form): void {
			$admin = $this->admin;
			if ($admin === null) {
				throw new BadRequestException('Administrador no encontrado.', 404);
			}

			try {
				if ($form['toggle']->isSubmittedBy()) {
					$updated = $this->toggleHandler->handle($admin->email, !$admin->active);
					$this->flashMessage(
						$updated->active ? 'Administrador activado.' : 'Administrador desactivado.',
						'success',
					);
					$this->redirect('detail', ['email' => $admin->email]);
				}

				if ($form['delete']->isSubmittedBy()) {
					$this->deleteHandler->handle($admin->email);
					$this->flashMessage('Administrador eliminado.', 'success');
					$this->redirect('list');
				}
			} catch (ApiError $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}


	private function loadAdmin(string $email): AdminUser
	{
		try {
			$admin = $this->getHandler->handle($email);
		} catch (ApiError $e) {
			$this->reportApiError($e);
		}

		if ($admin === null) {
			throw new BadRequestException('Administrador no encontrado.', 404);
		}

		return $admin;
	}


	private function showApiError(Form $form, ApiError $error): void
	{
		$field = $error->field();
		$control = $field !== null && isset($form[$field]) ? $form[$field] : null;

		if ($control instanceof BaseControl) {
			$control->addError($error->getMessage());
			return;
		}

		$form->addError($error->getMessage());
	}
}
