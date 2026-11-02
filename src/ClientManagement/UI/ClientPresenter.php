<?php declare(strict_types=1);

namespace App\ClientManagement\UI;

use App\ClientManagement\Application\CloneClientHandler;
use App\ClientManagement\Application\CreateClientHandler;
use App\ClientManagement\Application\DeleteClientHandler;
use App\ClientManagement\Application\ExportClientsHandler;
use App\ClientManagement\Application\GetClientHandler;
use App\ClientManagement\Application\ImportClientsHandler;
use App\ClientManagement\Application\ListClientsHandler;
use App\ClientManagement\Application\PurgeCacheHandler;
use App\ClientManagement\Application\RotateCryptIdHandler;
use App\ClientManagement\Application\RotateTokenHandler;
use App\ClientManagement\Application\UpdateClientHandler;
use App\ClientManagement\Domain\Client;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\TokenVault;
use App\Shared\UI\BasePresenter;
use JsonException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Http\FileUpload;
use Nette\Utils\ArrayHash;


final class ClientPresenter extends BasePresenter
{
	private const PAGE_SIZE = 50;

	private const EXPORT_FORMAT = 'teleproxy-clients/v1';

	private const IMPORT_MAX_CLIENTS = 100;

	private const IMPORT_MAX_BYTES = 10 * 1024 * 1024;

	private const FIELD_MAP = [
		'whitelist' => 'whitelist',
		'rate_limit' => 'rate_limit_max_requests',
		'max_scripting_body_bytes' => 'max_scripting_body_bytes',
		'scripting.code_hash' => 'scripting_code',
		'scripting.expression' => 'scripting_expression',
		'error_handling.fallback_urls' => 'fallback_urls',
		'header_rules' => 'header_rules',
	];

	private ?Client $client = null;


	public function __construct(
		private readonly ListClientsHandler $listHandler,
		private readonly GetClientHandler $getHandler,
		private readonly CreateClientHandler $createHandler,
		private readonly UpdateClientHandler $updateHandler,
		private readonly DeleteClientHandler $deleteHandler,
		private readonly RotateTokenHandler $rotateTokenHandler,
		private readonly RotateCryptIdHandler $rotateCryptIdHandler,
		private readonly CloneClientHandler $cloneHandler,
		private readonly PurgeCacheHandler $purgeCacheHandler,
		private readonly ExportClientsHandler $exportHandler,
		private readonly ImportClientsHandler $importHandler,
		private readonly ClientFormFactory $formFactory,
		private readonly TokenVault $tokenVault,
	) {}


	public function actionList(int $skip = 0): void
	{
		$skip = max(0, $skip);

		try {
			$result = $this->listHandler->handle($skip, self::PAGE_SIZE);
		} catch (ApiError $e) {
			$this->reportApiError($e);
		}

		$this->template->clients = $result['clients'];
		$this->template->total = $result['total'];
		$this->template->skip = $skip;
		$this->template->limit = self::PAGE_SIZE;
	}


	public function actionCreate(): void
	{
	}


	public function actionDetail(string $id): void
	{
		$this->client = $this->loadClient($id);
		$this->template->client = $this->client;
	}


	public function actionToken(string $handle): void
	{
		$payload = $this->tokenVault->consume($handle);
		if ($payload === null) {
			$this->tokenMissing();
		}

		$entries = $this->tokenEntries($payload);
		$results = $this->tokenResults($payload);
		if ($entries === [] && $results === []) {
			$this->tokenMissing();
		}

		$this->template->entries = $entries;
		$this->template->results = $results;
		$this->template->reason = $this->payloadString($payload, 'reason');
	}


	public function actionExport(): void
	{
		try {
			$clients = $this->exportHandler->handle();
		} catch (ApiError $e) {
			$this->reportApiError($e);
		}

		$this->getHttpResponse()->setHeader('Content-Disposition', 'attachment; filename="clients-export.json"');
		$this->sendJson([
			'format' => self::EXPORT_FORMAT,
			'exported_at' => gmdate('Y-m-d\TH:i:s\Z'),
			'clients' => $clients,
		]);
	}


	public function actionImport(): void
	{
	}


	protected function createComponentCreateForm(): Form
	{
		return $this->formFactory->createNew(function (bool $wildcard): void {
			try {
				$result = $this->createHandler->handle($wildcard);
			} catch (ApiError $e) {
				$this->flashMessage($e->getMessage(), 'danger');
				return;
			}

			$this->redirectToToken($result['token'], $result['client']->cryptId, 'Cliente creado');
		});
	}


	protected function createComponentClientForm(): Form
	{
		return $this->formFactory->createEdit(
			$this->client,
			function (array $payload, Form $form): void {
				$client = $this->client;
				if ($client === null) {
					throw new BadRequestException('Cliente no encontrado.', 404);
				}

				try {
					$version = $this->updateHandler->handle($client->internalId, $payload);
				} catch (ApiError $e) {
					$this->showFormApiError($form, $e);
					return;
				}

				$this->flashMessage('Configuración guardada. Versión ' . $version . '.', 'success');
				$this->redirect('detail', ['id' => $client->internalId]);
			},
		);
	}


	protected function createComponentActionsForm(): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addText('confirm_id', 'Escribe el crypt_id para confirmar el borrado');
		$form->addSubmit('rotateToken', 'Rotar token');
		$form->addSubmit('rotateCryptId', 'Rotar crypt_id');
		$form->addSubmit('purgeCache', 'Purgar caché');
		$form->addSubmit('clone', 'Clonar');
		$form->addSubmit('delete', 'Eliminar');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$client = $this->client;
			if ($client === null) {
				throw new BadRequestException('Cliente no encontrado.', 404);
			}

			try {
				if ($form['rotateToken']->isSubmittedBy()) {
					$this->rotateToken($client);
				} elseif ($form['rotateCryptId']->isSubmittedBy()) {
					$this->rotateCryptId($client);
				} elseif ($form['purgeCache']->isSubmittedBy()) {
					$this->purgeCache($client);
				} elseif ($form['clone']->isSubmittedBy()) {
					$this->cloneClient($client);
				} elseif ($form['delete']->isSubmittedBy()) {
					$this->deleteClient($form, $values, $client);
				}
			} catch (ApiError $e) {
				$form->addError($e->getMessage());
			}
		};

		return $form;
	}


	protected function createComponentImportForm(): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addTextArea('payload', 'JSON exportado');
		$form->addUpload('file', 'Archivo JSON (opcional)');
		$form->addSubmit('import', 'Importar clientes');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$json = $this->importJson($form, $values);
			if ($json === null) {
				return;
			}

			$clients = $this->parseImport($form, $json);
			if ($clients === null) {
				return;
			}

			$entries = [];
			$outcomes = [];
			foreach ($this->importHandler->handle($clients) as $result) {
				$cryptId = $result['crypt_id'];
				$token = $result['token'] ?? null;

				if ($result['status'] === 'created' && is_string($token) && $token !== '') {
					$entries[] = ['crypt_id' => $cryptId, 'token' => $token];
					$outcomes[] = ['crypt_id' => $cryptId, 'status' => 'created', 'message' => ''];
				} else {
					$outcomes[] = [
						'crypt_id' => $cryptId,
						'status' => $result['status'],
						'message' => $result['message'] ?? '',
					];
				}
			}

			$this->redirectToTokens('Importación completada', $entries, $outcomes);
		};

		return $form;
	}


	private function rotateToken(Client $client): void
	{
		$result = $this->rotateTokenHandler->handle($client->internalId);
		$this->redirectToToken($result['token'], $client->cryptId, 'Token rotado');
	}


	private function rotateCryptId(Client $client): void
	{
		$result = $this->rotateCryptIdHandler->handle($client->internalId);
		$this->flashMessage(
			'Nuevo crypt_id: ' . $result['new_crypt_id'] . '. Las URLs /aq/{crypt_id}/ cambian.',
			'success',
		);
		$this->redirect('detail', ['id' => $client->internalId]);
	}


	private function purgeCache(Client $client): void
	{
		$result = $this->purgeCacheHandler->handle($client->internalId);
		$this->flashMessage(
			sprintf(
				'Caché purgada: %d claves (%s) a las %s.',
				$result['purged_keys'],
				$result['pattern'],
				$result['purged_at'],
			),
			'success',
		);
		$this->redirect('detail', ['id' => $client->internalId]);
	}


	private function cloneClient(Client $client): void
	{
		$result = $this->cloneHandler->handle($client->internalId);
		$this->redirectToToken($result['token'], $result['client']->cryptId, 'Cliente clonado');
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function deleteClient(Form $form, ArrayHash $values, Client $client): void
	{
		$confirmed = $values['confirm_id'] ?? null;
		if (!is_string($confirmed) || trim($confirmed) !== $client->cryptId) {
			$form['confirm_id']->addError('Escribe exactamente el crypt_id del cliente para confirmar el borrado.');
			return;
		}

		$this->deleteHandler->handle($client->internalId);
		$this->flashMessage('Cliente eliminado.', 'success');
		$this->redirect('list');
	}


	private function loadClient(string $internalId): Client
	{
		try {
			$client = $this->getHandler->handle($internalId);
		} catch (ApiError $e) {
			$this->reportApiError($e);
		}

		if ($client === null) {
			throw new BadRequestException('Cliente no encontrado.', 404);
		}

		return $client;
	}


	private function redirectToToken(string $token, string $cryptId, string $reason): void
	{
		$this->redirectToTokens($reason, [['crypt_id' => $cryptId, 'token' => $token]], []);
	}


	/**
	 * @param list<array{crypt_id: string, token: string}> $entries
	 * @param list<array{crypt_id: string, status: string, message?: string}> $results
	 */
	private function redirectToTokens(string $reason, array $entries, array $results): void
	{
		$handle = $this->tokenVault->store([
			'reason' => $reason,
			'entries' => $entries,
			'results' => $results,
		]);

		$this->redirect('token', ['handle' => $handle]);
	}


	private function showFormApiError(Form $form, ApiError $error): void
	{
		$field = $error->field();
		$control = $field === null ? null : $this->resolveControl($form, $field);

		if ($control !== null) {
			$control->addError($error->getMessage());
		} else {
			$form->addError($error->getMessage());
		}
	}


	private function resolveControl(Form $form, string $field): ?BaseControl
	{
		$name = self::FIELD_MAP[$field] ?? $field;
		if (str_starts_with($field, 'header_rules[')) {
			$name = 'header_rules';
		} elseif (str_starts_with($field, 'preview.')) {
			$name = 'preview_enabled';
		}

		return isset($form[$name]) && $form[$name] instanceof BaseControl ? $form[$name] : null;
	}


	private function tokenMissing(): never
	{
		$this->flashMessage('El token ya se mostró o caducó. Si lo perdiste, rota el token del cliente.', 'danger');
		$this->redirect('list');
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function importJson(Form $form, ArrayHash $values): ?string
	{
		$upload = $values['file'] ?? null;
		if ($upload instanceof FileUpload && $upload->isOk()) {
			if ($upload->getSize() > self::IMPORT_MAX_BYTES) {
				$form->addError('El archivo supera el límite de 10 MB.');
				return null;
			}

			$contents = $upload->getContents();
			if (is_string($contents) && trim($contents) !== '') {
				return $contents;
			}
		}

		$payload = $values['payload'] ?? null;
		$json = is_string($payload) ? trim($payload) : '';
		if ($json === '') {
			$form->addError('Pega el JSON exportado o sube un archivo.');
			return null;
		}

		if (strlen($json) > self::IMPORT_MAX_BYTES) {
			$form->addError('El contenido supera el límite de 10 MB.');
			return null;
		}

		return $json;
	}


	/**
	 * @return list<array<string, mixed>>|null
	 */
	private function parseImport(Form $form, string $json): ?array
	{
		try {
			$decoded = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
		} catch (JsonException $e) {
			$form->addError('JSON inválido: ' . $e->getMessage());
			return null;
		}

		if (!is_array($decoded) || ($decoded['format'] ?? null) !== self::EXPORT_FORMAT) {
			$form->addError(
				'El archivo no es una exportación de TeleProxy (se esperaba format ' . self::EXPORT_FORMAT . ').',
			);
			return null;
		}

		$raw = $decoded['clients'] ?? null;
		if (!is_array($raw) || !array_is_list($raw) || $raw === []) {
			$form->addError('El archivo no contiene la lista de clientes.');
			return null;
		}

		if (count($raw) > self::IMPORT_MAX_CLIENTS) {
			$form->addError('El máximo es ' . self::IMPORT_MAX_CLIENTS . ' clientes por importación.');
			return null;
		}

		$clients = [];
		foreach ($raw as $entry) {
			if (!is_array($entry)) {
				$form->addError('El cliente #' . (count($clients) + 1) . ' no es un objeto JSON.');
				return null;
			}

			$clients[] = $entry;
		}

		return $clients;
	}


	/**
	 * @param array<string, mixed> $payload
	 * @return list<array{crypt_id: string, token: string}>
	 */
	private function tokenEntries(array $payload): array
	{
		$raw = $payload['entries'] ?? [];
		if (!is_array($raw)) {
			return [];
		}

		$entries = [];
		foreach ($raw as $item) {
			if (!is_array($item)) {
				continue;
			}

			$token = $item['token'] ?? null;
			if (!is_string($token) || $token === '') {
				continue;
			}

			$cryptId = $item['crypt_id'] ?? '';
			$entries[] = [
				'crypt_id' => is_string($cryptId) ? $cryptId : '',
				'token' => $token,
			];
		}

		return $entries;
	}


	/**
	 * @param array<string, mixed> $payload
	 * @return list<array{crypt_id: string, status: string, message: string}>
	 */
	private function tokenResults(array $payload): array
	{
		$raw = $payload['results'] ?? [];
		if (!is_array($raw)) {
			return [];
		}

		$results = [];
		foreach ($raw as $item) {
			if (!is_array($item)) {
				continue;
			}

			$status = $item['status'] ?? '';
			if (!is_string($status) || $status === '') {
				continue;
			}

			$cryptId = $item['crypt_id'] ?? '';
			$message = $item['message'] ?? '';
			$results[] = [
				'crypt_id' => is_string($cryptId) ? $cryptId : '',
				'status' => $status,
				'message' => is_string($message) ? $message : '',
			];
		}

		return $results;
	}


	/**
	 * @param array<string, mixed> $payload
	 */
	private function payloadString(array $payload, string $key): string
	{
		$value = $payload[$key] ?? '';

		return is_string($value) ? $value : '';
	}
}
