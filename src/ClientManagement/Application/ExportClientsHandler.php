<?php declare(strict_types=1);

namespace App\ClientManagement\Application;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ClientRepository;


final class ExportClientsHandler
{
	private const PAGE_SIZE = 200;


	public function __construct(
		private readonly ClientRepository $clientRepository,
	) {}


	/**
	 * @return list<array<string, mixed>>
	 */
	public function handle(): array
	{
		$exported = [];
		$skip = 0;

		do {
			$page = $this->clientRepository->list(self::PAGE_SIZE, $skip);

			foreach ($page['clients'] as $summary) {
				$client = $this->clientRepository->get($summary->internalId);
				if ($client !== null) {
					$exported[] = $this->toExport($client);
				}
			}

			$skip += self::PAGE_SIZE;
		} while ($skip < $page['total'] && $page['clients'] !== []);

		return $exported;
	}


	/**
	 * @return array<string, mixed>
	 */
	private function toExport(Client $client): array
	{
		return [
			'crypt_id' => $client->cryptId,
			'kind' => $client->kind,
			'wildcard' => $client->wildcard,
			'whitelist' => $client->whitelist->entries(),
			'rate_limit' => $client->rateLimit->toArray(),
			'max_scripting_body_bytes' => $client->maxScriptingBodyBytes,
			'scripting' => [
				'enabled' => $client->scripting->enabled,
				'code' => $client->scripting->code,
				'expression' => $client->scripting->expression,
			],
			'error_handling' => $client->errorHandling,
			'header_rules' => $client->headerRules,
			'preview' => $client->preview->toArray(),
		];
	}
}
