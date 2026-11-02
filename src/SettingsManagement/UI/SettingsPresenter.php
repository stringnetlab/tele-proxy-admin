<?php declare(strict_types=1);

namespace App\SettingsManagement\UI;

use App\SettingsManagement\Application\GetSettingsHandler;
use App\SettingsManagement\Application\UpdateSettingsHandler;
use App\SettingsManagement\Domain\Settings;
use App\Shared\Infrastructure\ApiError;
use App\Shared\UI\BasePresenter;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\InvalidStateException;
use Nette\Utils\ArrayHash;


final class SettingsPresenter extends BasePresenter
{
	private const RANGES = [
		'upstream_timeout_secs' => [1, 300],
		'max_response_bytes' => [1_048_576, 536_870_912],
		'cacheable_size_bytes' => [1_024, 536_870_912],
		'stale_cache_ttl_secs' => [60, 604_800],
		'cache_ttl_images_secs' => [1, 604_800],
		'cache_ttl_assets_secs' => [1, 604_800],
		'cache_ttl_default_secs' => [1, 604_800],
		'lua_timeout_ms' => [1, 60_000],
		'lua_memory_limit_mb' => [1, 1_024],
		'webhook_timeout_ms' => [1, 60_000],
		'fallback_timeout_ms' => [1, 60_000],
	];

	private const USER_AGENT_MAX_LENGTH = 256;

	private ?Settings $settings = null;


	public function __construct(
		private readonly GetSettingsHandler $getHandler,
		private readonly UpdateSettingsHandler $updateHandler,
	) {}


	public function actionDefault(): void
	{
		try {
			$this->settings = $this->getHandler->handle();
		} catch (ApiError $e) {
			$this->reportApiError($e);
		}

		$this->template->settings = $this->settings;
	}


	protected function createComponentSettingsForm(): Form
	{
		$settings = $this->currentSettings();

		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addText('upstream_user_agent', 'User-Agent del origen')
			->setDefaultValue($settings->upstreamUserAgent)
			->setHtmlAttribute('maxlength', (string) self::USER_AGENT_MAX_LENGTH);
		$form->addInteger('upstream_timeout_secs', 'Timeout del origen (s)')
			->setDefaultValue($settings->upstreamTimeoutSecs);
		$form->addInteger('max_response_bytes', 'Tamaño máximo de respuesta (bytes)')
			->setDefaultValue($settings->maxResponseBytes);
		$form->addInteger('cacheable_size_bytes', 'Tamaño máximo cacheable (bytes)')
			->setDefaultValue($settings->cacheableSizeBytes);
		$form->addInteger('stale_cache_ttl_secs', 'TTL de caché obsoleta (s)')
			->setDefaultValue($settings->staleCacheTtlSecs);
		$form->addInteger('cache_ttl_images_secs', 'TTL caché de imágenes (s)')
			->setDefaultValue($settings->cacheTtlImagesSecs);
		$form->addInteger('cache_ttl_assets_secs', 'TTL caché de assets (s)')
			->setDefaultValue($settings->cacheTtlAssetsSecs);
		$form->addInteger('cache_ttl_default_secs', 'TTL caché por defecto (s)')
			->setDefaultValue($settings->cacheTtlDefaultSecs);
		$form->addInteger('lua_timeout_ms', 'Timeout de Lua (ms)')
			->setDefaultValue($settings->luaTimeoutMs);
		$form->addInteger('lua_memory_limit_mb', 'Memoria máxima de Lua (MB)')
			->setDefaultValue($settings->luaMemoryLimitMb);
		$form->addInteger('webhook_timeout_ms', 'Timeout de webhooks (ms)')
			->setDefaultValue($settings->webhookTimeoutMs);
		$form->addInteger('fallback_timeout_ms', 'Timeout de fallbacks (ms)')
			->setDefaultValue($settings->fallbackTimeoutMs);
		$form->addCheckbox('verbose_errors', 'verbose_errors')
			->setDefaultValue($settings->verboseErrors);
		$form->addSubmit('save', 'Guardar cambios');

		$form->onSuccess[] = function (Form $form, ArrayHash $values): void {
			$next = $this->buildSettings($form, $values);
			if ($next === null) {
				return;
			}

			$patch = $this->diff($this->currentSettings(), $next);
			if ($patch === []) {
				$form->addError('No has cambiado ningún ajuste.');
				return;
			}

			try {
				$updated = $this->updateHandler->handle($patch);
			} catch (ApiError $e) {
				if ($e->status === 409) {
					$form->addError('La configuración cambió mientras editabas. Recarga la página y vuelve a intentarlo.');
					return;
				}

				$this->showApiError($form, $e);
				return;
			}

			$this->flashMessage('Ajustes guardados.', 'success');
			if ($updated->verboseErrors) {
				$this->flashMessage(
					'verbose_errors está activo: los errores de /aq/ y de las APIs exponen el motivo interno.',
					'warning',
				);
			}
			$this->redirect('default');
		};

		return $form;
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function buildSettings(Form $form, ArrayHash $values): ?Settings
	{
		$valid = true;

		/** @var array<string, int> $numbers */
		$numbers = [];

		foreach (self::RANGES as $field => [$min, $max]) {
			$value = $this->intValue($form, $values, $field, $min, $max);
			if ($value === null) {
				$valid = false;
			} else {
				$numbers[$field] = $value;
			}
		}

		$rawUserAgent = $values['upstream_user_agent'] ?? '';
		$userAgent = is_string($rawUserAgent) ? trim($rawUserAgent) : '';
		$control = $this->control($form, 'upstream_user_agent');

		if (strlen($userAgent) > self::USER_AGENT_MAX_LENGTH) {
			$control?->addError('No puede superar los ' . self::USER_AGENT_MAX_LENGTH . ' caracteres.');
			$valid = false;
		}

		if (preg_match('/[\x00-\x1F\x7F]/', $userAgent) === 1) {
			$control?->addError('No puede contener caracteres de control (saltos de línea, etc.).');
			$valid = false;
		}

		if (!$valid) {
			return null;
		}

		if ($numbers['cacheable_size_bytes'] > $numbers['max_response_bytes']) {
			$this->control($form, 'cacheable_size_bytes')
				?->addError('No puede superar max_response_bytes (' . $numbers['max_response_bytes'] . ').');
			return null;
		}

		return new Settings(
			upstreamUserAgent: $userAgent,
			upstreamTimeoutSecs: $numbers['upstream_timeout_secs'],
			maxResponseBytes: $numbers['max_response_bytes'],
			cacheableSizeBytes: $numbers['cacheable_size_bytes'],
			staleCacheTtlSecs: $numbers['stale_cache_ttl_secs'],
			cacheTtlImagesSecs: $numbers['cache_ttl_images_secs'],
			cacheTtlAssetsSecs: $numbers['cache_ttl_assets_secs'],
			cacheTtlDefaultSecs: $numbers['cache_ttl_default_secs'],
			luaTimeoutMs: $numbers['lua_timeout_ms'],
			luaMemoryLimitMb: $numbers['lua_memory_limit_mb'],
			webhookTimeoutMs: $numbers['webhook_timeout_ms'],
			fallbackTimeoutMs: $numbers['fallback_timeout_ms'],
			verboseErrors: (bool) ($values['verbose_errors'] ?? false),
		);
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function intValue(Form $form, ArrayHash $values, string $field, int $min, int $max): ?int
	{
		$value = $values[$field] ?? null;
		if (!is_int($value)) {
			$this->control($form, $field)?->addError('Debe ser un número entero.');
			return null;
		}

		if ($value < $min || $value > $max) {
			$this->control($form, $field)?->addError(
				sprintf('Debe estar entre %s y %s.', number_format($min, 0, ',', '.'), number_format($max, 0, ',', '.')),
			);
			return null;
		}

		return $value;
	}


	/**
	 * @return array<string, mixed>
	 */
	private function diff(Settings $current, Settings $next): array
	{
		$before = $current->toArray();
		$after = $next->toArray();

		$patch = [];
		foreach (array_keys(Settings::FIELDS) as $apiField) {
			if ($after[$apiField] !== $before[$apiField]) {
				$patch[$apiField] = $after[$apiField];
			}
		}

		return $patch;
	}


	private function currentSettings(): Settings
	{
		return $this->settings ?? throw new InvalidStateException('Los ajustes no se cargaron antes de construir el formulario.');
	}


	private function control(Form $form, string $name): ?BaseControl
	{
		$control = isset($form[$name]) ? $form[$name] : null;

		return $control instanceof BaseControl ? $control : null;
	}


	private function showApiError(Form $form, ApiError $error): void
	{
		$field = $error->field();
		$control = $field !== null ? $this->control($form, $field) : null;

		if ($control !== null) {
			$control->addError($error->getMessage());
			return;
		}

		$form->addError($error->getMessage());
	}
}
