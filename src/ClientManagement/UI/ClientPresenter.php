<?php declare(strict_types=1);

namespace App\ClientManagement\UI;

use App\ClientManagement\Application\CloneClientHandler;
use App\ClientManagement\Application\CreateClientHandler;
use App\ClientManagement\Application\DeleteClientHandler;
use App\ClientManagement\Application\GetClientHandler;
use App\ClientManagement\Application\ListClientsHandler;
use App\ClientManagement\Application\RotateCryptIdHandler;
use App\ClientManagement\Application\RotateTokenHandler;
use App\ClientManagement\Application\UpdateClientHandler;
use App\Shared\UI\BasePresenter;
use Nette\Application\UI\Form;


final class ClientPresenter extends BasePresenter
{
	public function __construct(
		private readonly ListClientsHandler $listHandler,
		private readonly GetClientHandler $getHandler,
		private readonly CreateClientHandler $createHandler,
		private readonly UpdateClientHandler $updateHandler,
		private readonly DeleteClientHandler $deleteHandler,
		private readonly RotateTokenHandler $rotateTokenHandler,
		private readonly RotateCryptIdHandler $rotateCryptIdHandler,
		private readonly CloneClientHandler $cloneHandler,
	) {}


	public function startup(): void
	{
		parent::startup();
		$this->requireAdmin();
	}


	public function actionList(int $page = 1): void
	{
		$result = $this->listHandler->handle($page);
		$this->template->clients = $result['clients'];
		$this->template->total = $result['total'];
		$this->template->page = $page;
	}


	public function actionDetail(string $id): void
	{
		$client = $this->getHandler->handle($id);
		if ($client === null) {
			$this->error('Cliente no encontrado', 404);
		}
		$this->template->client = $client;
	}


	public function actionCreate(): void
	{
	}


	public function actionEdit(string $id): void
	{
		$client = $this->getHandler->handle($id);
		if ($client === null) {
			$this->error('Cliente no encontrado', 404);
		}
		$this->template->client = $client;
		$this['clientForm']->setDefaults($client->toArray());
	}


	public function actionDelete(string $id): void
	{
		$this->deleteHandler->handle($id);
		$this->flashMessage('Cliente eliminado', 'success');
		$this->redirect('list');
	}


	public function actionRotateToken(string $id): void
	{
		$result = $this->rotateTokenHandler->handle($id);
		$this->flashMessage('Token rotado: ' . $result['token'], 'success');
		$this->redirect('detail', ['id' => $id]);
	}


	public function actionRotateCryptId(string $id): void
	{
		$result = $this->rotateCryptIdHandler->handle($id);
		$this->flashMessage('Crypt ID rotado: ' . $result['new_crypt_id'], 'success');
		$this->redirect('detail', ['id' => $id]);
	}


	public function actionClone(string $id): void
	{
		$client = $this->getHandler->handle($id);
		if ($client === null) {
			$this->error('Cliente no encontrado', 404);
		}
		$newName = $client->name . ' (copia)';
		$newClient = $this->cloneHandler->handle($id, $newName);
		$this->flashMessage('Cliente clonado', 'success');
		$this->redirect('detail', ['id' => $newClient->cryptId]);
	}


	public function actionExport(): void
	{
		$result = $this->listHandler->handle(1, 10000);
		$this->getHttpResponse()->setHeader('Content-Type', 'application/json');
		$this->getHttpResponse()->setHeader('Content-Disposition', 'attachment; filename="clients-export.json"');
		$this->sendJson($result['clients']);
	}


	protected function createComponentClientForm(): Form
	{
		$form = new Form;
		$form->addText('name', 'Nombre')
			->setRequired('El nombre es obligatorio');
		$form->addText('domain', 'Dominio')
			->setRequired('El dominio es obligatorio');
		$form->addTextArea('whitelist', 'Whitelist (un dominio por línea)');
		$form->addInteger('rate_limit_requests', 'Rate limit (peticiones)')
			->setDefaultValue(50);
		$form->addInteger('rate_limit_window', 'Ventana (segundos)')
			->setDefaultValue(60);
		$form->addSubmit('save', 'Guardar');

		$form->onSuccess[] = function (Form $form, \stdClass $values): void {
			$id = $this->getParameter('id');
			$data = [
				'name' => $values->name,
				'domain' => $values->domain,
				'whitelist' => array_filter(explode("\n", trim($values->whitelist ?? ''))),
				'rate_limit' => [
					'max_requests' => $values->rate_limit_requests,
					'window_seconds' => $values->rate_limit_window,
				],
			];

			if ($id) {
				$version = (int) ($form->getHttpData(Form::DATA_HIDDEN, 'config_version') ?? 0);
				$this->updateHandler->handle($id, $data, $version > 0 ? $version : null);
				$this->flashMessage('Cliente actualizado', 'success');
				$this->redirect('detail', ['id' => $id]);
			} else {
				$client = $this->createHandler->handle($data);
				$this->flashMessage('Cliente creado', 'success');
				$this->redirect('detail', ['id' => $client->cryptId]);
			}
		};

		return $form;
	}
}
