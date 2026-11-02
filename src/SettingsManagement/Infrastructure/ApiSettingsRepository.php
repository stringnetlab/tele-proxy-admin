<?php declare(strict_types=1);

namespace App\SettingsManagement\Infrastructure;

use App\SettingsManagement\Domain\Settings;
use App\SettingsManagement\Domain\SettingsRepository;
use App\Shared\Infrastructure\ApiClient;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiSession;
use InvalidArgumentException;


class ApiSettingsRepository implements SettingsRepository
{
	private const PATH = '/api/v1/admin/settings';


	public function __construct(
		private readonly ApiClient $apiClient,
		private readonly ApiSession $apiSession,
	) {}


	public function get(): Settings
	{
		return $this->map($this->http()->json('GET', self::PATH));
	}


	public function patch(array $patch): Settings
	{
		return $this->map($this->http()->json('PATCH', self::PATH, ['json' => $patch]));
	}


	private function http(): ApiClient
	{
		$cookie = $this->apiSession->cookie();

		return $cookie === null || $cookie === '' ? $this->apiClient : $this->apiClient->withSessionCookie($cookie);
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private function map(array $data): Settings
	{
		try {
			return Settings::fromApiResponse($data);
		} catch (InvalidArgumentException $e) {
			throw new ApiError(
				502,
				'invalid_response',
				'Respuesta inválida del servicio de administración: ' . $e->getMessage(),
			);
		}
	}
}
