<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\ClientRepository;
use App\ClientManagement\Domain\ValueObjects\Scripting;
use App\Shared\Infrastructure\ApiError;
use Throwable;


final class ImportClientsHandler
{
	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @param list<array<string, mixed>> $clients
	 * @return list<array{crypt_id: string, status: string, token?: string, message?: string}>
	 */
	public function handle(array $clients): array
	{
		$results = [];

		foreach ($clients as $entry) {
			$created = null;

			try {
				$created = $this->clientRepository->create((bool) ($entry['wildcard'] ?? false));
			} catch (ApiError $e) {
				$results[] = ['crypt_id' => '', 'status' => 'failed', 'message' => $e->getMessage()];
				continue;
			}

			try {
				$this->clientRepository->update($created['client']->internalId, $this->updatePayload($entry));
				$results[] = [
					'crypt_id' => $created['client']->cryptId,
					'status' => 'created',
					'token' => $created['token'],
				];
			} catch (Throwable $e) {
				try {
					$this->clientRepository->delete($created['client']->internalId);
				} catch (Throwable) {
				}

				$results[] = [
					'crypt_id' => $created['client']->cryptId,
					'status' => 'failed',
					'message' => $e instanceof ApiError ? $e->getMessage() : 'No se pudo aplicar la configuración importada.',
				];
			}
		}

		return $results;
	}


	/**
	 * @param array<string, mixed> $entry
	 * @return array<string, mixed>
	 */
	private function updatePayload(array $entry): array
	{
		$payload = [];

		if (is_array($entry['whitelist'] ?? null)) {
			$payload['whitelist'] = array_values(array_filter($entry['whitelist'], 'is_string'));
		}

		if (is_array($entry['rate_limit'] ?? null)) {
			$payload['rate_limit'] = [
				'max_requests' => (int) ($entry['rate_limit']['max_requests'] ?? 0),
				'window_seconds' => (int) ($entry['rate_limit']['window_seconds'] ?? 0),
			];
		}

		if (is_numeric($entry['max_scripting_body_bytes'] ?? null)) {
			$payload['max_scripting_body_bytes'] = (int) $entry['max_scripting_body_bytes'];
		}

		$scripting = is_array($entry['scripting'] ?? null) ? $entry['scripting'] : [];
		$code = is_string($scripting['code'] ?? null) ? $scripting['code'] : '';
		$scriptingPayload = [
			'enabled' => (bool) ($scripting['enabled'] ?? false),
			'code' => $code,
			'expression' => is_string($scripting['expression'] ?? null) ? $scripting['expression'] : '',
		];

		if ($code !== '') {
			$scriptingPayload['code_hash'] = Scripting::hash($code);
		}

		$payload['scripting'] = $scriptingPayload;

		if (is_array($entry['error_handling'] ?? null)) {
			$payload['error_handling'] = $entry['error_handling'];
		}

		if (is_array($entry['header_rules'] ?? null)) {
			$payload['header_rules'] = array_values(array_filter($entry['header_rules'], 'is_array'));
		}

		if (is_array($entry['preview'] ?? null)) {
			$payload['preview'] = $entry['preview'];
		}

		return $payload;
	}
}
