<?php declare(strict_types=1);

namespace App\AdminManagement\UI;

use App\AdminManagement\Application\AddAdminHandler;
use App\AdminManagement\Application\DeleteAdminHandler;
use App\AdminManagement\Application\GetAdminHandler;
use App\AdminManagement\Application\ListAdminsHandler;
use App\AdminManagement\Application\ToggleAdminHandler;
use App\Shared\UI\BasePresenter;
use Nette\Application\UI\Form;


final class AdminPresenter extends BasePresenter
{
	public function __construct(
		private readonly ListAdminsHandler $listHandler,
		private readonly GetAdminHandler $getHandler,
		private readonly AddAdminHandler $addHandler,
		private readonly ToggleAdminHandler $toggleHandler,
		private readonly DeleteAdminHandler $deleteHandler,
	) {}


	public function startup(): void
	{
		parent::startup();
		$this->requireAdmin();
	}


	public function actionList(): void
	{
		$this->template->admins = $this->listHandler->handle();
	}


	public function actionDetail(string $email): void
	{
		$admin = $this->getHandler->handle($email);
		if ($admin === null) {
			$this->error('Administrador no encontrado', 404);
		}
		$this->template->admin = $admin;
	}


	public function actionCreate(): void
	{
	}


	public function actionToggle(string $email): void
	{
		$this->toggleHandler->handle($email);
		$this->flashMessage('Estado del administrador actualizado', 'success');
		$this->redirect('list');
	}


	public function actionDelete(string $email): void
	{
		$this->deleteHandler->handle($email);
		$this->flashMessage('Administrador eliminado', 'success');
		$this->redirect('list');
	}


	protected function createComponentAdminForm(): Form
	{
		$form = new Form;
		$form->addEmail('email', 'Email')
			->setRequired('El email es obligatorio');
		$form->addSelect('role', 'Rol', [
			'admin' => 'Admin',
			'master' => 'Master',
		])->setDefaultValue('admin');
		$form->addSubmit('save', 'Guardar');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$this->addHandler->handle($values->email, $values->role);
			$this->flashMessage('Administrador creado', 'success');
			$this->redirect('list');
		};

		return $form;
	}
}
