<?php declare(strict_types=1);

namespace App\AdminManagement\Infrastructure;

use App\AdminManagement\Domain\AdminRepository;
use App\AdminManagement\Domain\AdminUser;
use App\Shared\Infrastructure\ApiClient;
use App\Shared\Infrastructure\ApiError;
use App\Shared\Infrastructure\ApiSession;
use InvalidArgumentException;


class ApiAdminRepository implements AdminRepository
{
	private const BASE_PATH = '/api/v1/admin/admins';


	public function __construct(
		private readonly ApiClient $apiClient,
		private readonly ApiSession $apiSession,
	) {}


	public function list(): array
	{
		$data = $this->http()->json('GET', self::BASE_PATH);
		$raw = $data['admins'] ?? null;
		if (!is_array($raw)) {
			throw new ApiError(502, 'invalid_response', 'Respuesta inválida del servicio de administración: falta "admins".');
		}

		$admins = [];
		foreach ($raw as $item) {
			if (!is_array($item)) {
				throw new ApiError(502, 'invalid_response', 'Respuesta inválida del servicio de administración: administrador no válido.');
			}

			$admins[] = $this->map($item);
		}

		return $admins;
	}


	public function get(string $email): ?AdminUser
	{
		foreach ($this->list() as $admin) {
			if ($admin->email === $email) {
				return $admin;
			}
		}

		return null;
	}


	public function add(string $email): AdminUser
	{
		return $this->map(
			$this->http()->json('POST', self::BASE_PATH, ['json' => ['email' => $email]]),
		);
	}


	public function setActive(string $email, bool $active): AdminUser
	{
		return $this->map(
			$this->http()->json('PATCH', $this->path($email), ['json' => ['active' => $active]]),
		);
	}


	public function delete(string $email): void
	{
		$this->http()->json('DELETE', $this->path($email));
	}


	private function http(): ApiClient
	{
		$cookie = $this->apiSession->cookie();

		return $cookie === null || $cookie === '' ? $this->apiClient : $this->apiClient->withSessionCookie($cookie);
	}


	private function path(string $email): string
	{
		return self::BASE_PATH . '/' . rawurlencode($email);
	}


	/**
	 * @param array<string, mixed> $data
	 */
	private function map(array $data): AdminUser
	{
		try {
			return AdminUser::fromApiResponse($data);
		} catch (InvalidArgumentException $e) {
			throw new ApiError(
				502,
				'invalid_response',
				'Respuesta inválida del servicio de administración: ' . $e->getMessage(),
			);
		}
	}
}
