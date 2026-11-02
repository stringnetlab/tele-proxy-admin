<?php declare(strict_types=1);

namespace App\ClientManagement\UI;

use App\ClientManagement\Domain\Client;
use App\ClientManagement\Domain\ValueObjects\Preview;
use App\ClientManagement\Domain\ValueObjects\RateLimit;
use App\ClientManagement\Domain\ValueObjects\Scripting;
use App\ClientManagement\Domain\ValueObjects\Whitelist;
use InvalidArgumentException;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\ArrayHash;


final class ClientFormFactory
{
	private const MIN_SCRIPTING_BODY_BYTES = 1_048_576;

	private const MAX_SCRIPTING_BODY_BYTES = 52_428_800;

	private const MAX_HEADER_RULES = 50;

	private const MAX_HEADERS_PER_RULE = 10;

	private const MAX_HEADER_NAME_BYTES = 128;

	private const MAX_EXPRESSION_BYTES = 4096;

	private const MAX_HEADER_VALUE_BYTES = 4096;


	/**
	 * @param callable(bool): void $onSubmit
	 */
	public function createNew(callable $onSubmit): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');
		$form->addCheckbox('wildcard', 'Salida libre (wildcard)');
		$form->addSubmit('create', 'Crear cliente');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($onSubmit): void {
			$onSubmit($this->bool($values, 'wildcard'));
		};

		return $form;
	}


	/**
	 * @param callable(array<string, mixed>, Form): void $onSubmit
	 */
	public function createEdit(?Client $client, callable $onSubmit): Form
	{
		$form = new Form;
		$form->addProtection('La sesión expiró. Recarga la página e inténtalo de nuevo.');

		$form->addCheckbox('wildcard', 'Salida libre (wildcard; ignora la whitelist)')
			->setDefaultValue($client->wildcard ?? false);

		$form->addTextArea('whitelist', 'Whitelist (una entrada por línea: host o host:puerto)')
			->setDefaultValue($client?->whitelist->toText() ?? '')
			->setHtmlAttribute('rows', 6);

		$form->addInteger('rate_limit_max_requests', 'Peticiones por ventana')
			->setDefaultValue($client?->rateLimit->maxRequests ?? 50);
		$form->addInteger('rate_limit_window_seconds', 'Ventana (segundos)')
			->setDefaultValue($client?->rateLimit->windowSeconds ?? 60);

		$form->addInteger('max_scripting_body_bytes', 'Cuerpo máximo para scripting (bytes)')
			->setDefaultValue($client->maxScriptingBodyBytes ?? 5_242_880);

		$form->addCheckbox('scripting_enabled', 'Scripting Lua activado')
			->setDefaultValue($client?->scripting->enabled ?? false);
		$form->addTextArea('scripting_code', 'Código Lua')
			->setDefaultValue($client?->scripting->code ?? '')
			->setHtmlAttribute('rows', 12);
		$form->addText('scripting_expression', 'Expresión (vacío = siempre)')
			->setDefaultValue($client?->scripting->expression ?? '');

		$form->addSelect('error_handling_mode', 'Modo de error', [
			'transparent' => 'transparent',
			'wrapped' => 'wrapped',
		])->setDefaultValue($client?->errorHandling['mode'] ?? 'transparent');

		$form->addTextArea('fallback_urls', 'Fallbacks (JSON: { "<mime>": { "url", "hash" } })')
			->setDefaultValue($this->json($client?->errorHandling['fallback_urls'] ?? []))
			->setHtmlAttribute('rows', 6);

		$form->addTextArea('header_rules', 'Reglas de headers (JSON)')
			->setDefaultValue($this->json($client->headerRules ?? []))
			->setHtmlAttribute('rows', 6);

		$preview = $client?->preview;

		$form->addCheckbox('preview_enabled', 'Preview /pv/ activado')
			->setDefaultValue($preview->enabled ?? false);
		$form->addCheckbox('preview_meta_enabled', 'Metadatos /mt/ activados')
			->setDefaultValue($preview->metaEnabled ?? true);
		$form->addCheckbox('preview_cap_meta', 'Capacidad: meta')->setDefaultValue($preview->capabilityMeta ?? true);
		$form->addCheckbox('preview_cap_content', 'Capacidad: content')->setDefaultValue($preview->capabilityContent ?? true);
		$form->addCheckbox('preview_cap_screenshot', 'Capacidad: screenshot')->setDefaultValue($preview->capabilityScreenshot ?? false);
		$form->addCheckbox('preview_cap_pdf', 'Capacidad: pdf')->setDefaultValue($preview->capabilityPdf ?? false);

		$form->addInteger('preview_rate_limit_max_requests', 'Preview: peticiones por ventana')
			->setDefaultValue($preview->rateLimit->maxRequests ?? Preview::DEFAULT_RATE_LIMIT_REQUESTS);
		$form->addInteger('preview_rate_limit_window_seconds', 'Preview: ventana (segundos)')
			->setDefaultValue($preview->rateLimit->windowSeconds ?? Preview::DEFAULT_RATE_LIMIT_WINDOW_SECONDS);

		$form->addInteger('preview_ttl_default_seconds', 'Preview: TTL por defecto (s)')
			->setDefaultValue($preview->ttlDefaultSeconds ?? Preview::DEFAULT_TTL_SECONDS);
		$form->addInteger('preview_ttl_max_seconds', 'Preview: TTL máximo (s)')
			->setDefaultValue($preview->ttlMaxSeconds ?? Preview::DEFAULT_TTL_MAX_SECONDS);

		$form->addInteger('preview_max_render_timeout_ms', 'Preview: timeout de render (ms)')
			->setDefaultValue($preview->maxRenderTimeoutMs ?? Preview::DEFAULT_RENDER_TIMEOUT_MS);
		$form->addInteger('preview_max_body_bytes', 'Preview: cuerpo máximo (bytes)')
			->setDefaultValue($preview->maxBodyBytes ?? Preview::DEFAULT_BODY_BYTES);

		$form->addCheckbox('preview_max_full_page', 'Captura de página completa')
			->setDefaultValue($preview->screenshotMaxFullPage ?? true);
		$form->addInteger('preview_viewport_width', 'Viewport: ancho')
			->setDefaultValue($preview->viewportWidth ?? 1920);
		$form->addInteger('preview_viewport_height', 'Viewport: alto')
			->setDefaultValue($preview->viewportHeight ?? 1080);

		$form->addSubmit('save', 'Guardar configuración');

		$form->onSuccess[] = function (Form $form, ArrayHash $values) use ($client, $onSubmit): void {
			$payload = $this->buildPayload($form, $values, $client);
			if ($payload !== null) {
				$onSubmit($payload, $form);
			}
		};

		return $form;
	}


	/**
	 * @param ArrayHash<mixed> $values
	 * @return array<string, mixed>|null
	 */
	private function buildPayload(Form $form, ArrayHash $values, ?Client $client): ?array
	{
		$valid = true;

		$whitelist = null;
		try {
			$whitelist = Whitelist::fromLines($this->str($values, 'whitelist'));
		} catch (InvalidArgumentException $e) {
			$form['whitelist']->addError($e->getMessage());
			$valid = false;
		}

		$rateLimit = null;
		try {
			$rateLimit = new RateLimit(
				$this->int($values, 'rate_limit_max_requests'),
				$this->int($values, 'rate_limit_window_seconds'),
			);
		} catch (InvalidArgumentException $e) {
			$form['rate_limit_max_requests']->addError($e->getMessage());
			$valid = false;
		}

		$maxScriptingBody = $this->int($values, 'max_scripting_body_bytes');
		if ($maxScriptingBody < self::MIN_SCRIPTING_BODY_BYTES || $maxScriptingBody > self::MAX_SCRIPTING_BODY_BYTES) {
			$form['max_scripting_body_bytes']->addError(
				sprintf(
					'Debe estar entre %d y %d bytes.',
					self::MIN_SCRIPTING_BODY_BYTES,
					self::MAX_SCRIPTING_BODY_BYTES,
				),
			);
			$valid = false;
		}

		$code = $this->str($values, 'scripting_code');
		$scripting = null;
		try {
			$scripting = new Scripting(
				$this->bool($values, 'scripting_enabled'),
				$code,
				$code === '' ? ($client?->scripting->codeHash ?? '') : Scripting::hash($code),
				$this->str($values, 'scripting_expression'),
			);
		} catch (InvalidArgumentException $e) {
			$form['scripting_expression']->addError($e->getMessage());
			$valid = false;
		}

		$mode = $this->str($values, 'error_handling_mode');
		if (!in_array($mode, ['transparent', 'wrapped'], true)) {
			$form['error_handling_mode']->addError('Elige transparent o wrapped.');
			$valid = false;
		}

		$fallbacks = $this->parseFallbacks($form, $this->str($values, 'fallback_urls'));
		if ($fallbacks === null) {
			$valid = false;
		}

		$headerRules = $this->parseHeaderRules($form, $this->str($values, 'header_rules'));
		if ($headerRules === null) {
			$valid = false;
		}

		$preview = $this->parsePreview($form, $values);
		if ($preview === null) {
			$valid = false;
		}

		if (!$valid) {
			return null;
		}

		if ($whitelist === null || $rateLimit === null) {
			return null;
		}

		return [
			'wildcard' => $this->bool($values, 'wildcard'),
			'whitelist' => $whitelist->entries(),
			'rate_limit' => $rateLimit->toArray(),
			'max_scripting_body_bytes' => $maxScriptingBody,
			'scripting' => $scripting->toUpdatePayload(),
			'error_handling' => ['mode' => $mode, 'fallback_urls' => $fallbacks],
			'header_rules' => $headerRules,
			'preview' => $preview->toArray(),
		];
	}


	/**
	 * @return array<string, array{url: string, hash: string}>|null
	 */
	private function parseFallbacks(Form $form, string $json): ?array
	{
		if (trim($json) === '') {
			return [];
		}

		$decoded = json_decode($json, true);
		if (!is_array($decoded)) {
			$form['fallback_urls']->addError('El JSON de fallbacks no es válido.');

			return null;
		}

		$fallbacks = [];
		foreach ($decoded as $mime => $entry) {
			if (!is_string($mime) || !is_array($entry)) {
				$form['fallback_urls']->addError('Cada fallback debe ser un objeto { "<mime>": { "url", "hash" } }.');

				return null;
			}

			$url = $entry['url'] ?? null;
			$hash = $entry['hash'] ?? null;

			if (!is_string($url) || $url === '' || $this->isInvalidFallbackUrl($url)) {
				$form['fallback_urls']->addError("El fallback '$mime' necesita una URL http(s) válida.");

				return null;
			}

			if (!is_string($hash) || preg_match('/^sha256:[a-f0-9]{64}$/i', $hash) !== 1) {
				$form['fallback_urls']->addError("El fallback '$mime' necesita un hash con formato sha256:<64 hex>.");

				return null;
			}

			$fallbacks[$mime] = ['url' => $url, 'hash' => $hash];
		}

		return $fallbacks;
	}


	/**
	 * @return list<array<string, mixed>>|null
	 */
	private function parseHeaderRules(Form $form, string $json): ?array
	{
		if (trim($json) === '') {
			return [];
		}

		$decoded = json_decode($json, true);
		if (!is_array($decoded) || !array_is_list($decoded)) {
			$form['header_rules']->addError('El JSON de reglas de headers debe ser una lista.');

			return null;
		}

		if (count($decoded) > self::MAX_HEADER_RULES) {
			$form['header_rules']->addError('No puede haber más de ' . self::MAX_HEADER_RULES . ' reglas.');

			return null;
		}

		$rules = [];
		foreach ($decoded as $index => $rule) {
			$normalized = $this->parseHeaderRule($form, $index, $rule);
			if ($normalized === null) {
				return null;
			}

			$rules[] = $normalized;
		}

		return $rules;
	}


	/**
	 * @return array<string, mixed>|null
	 */
	private function parseHeaderRule(Form $form, int $index, mixed $rule): ?array
	{
		$label = 'header_rules[' . $index . ']';

		if (!is_array($rule)) {
			$form['header_rules']->addError("$label debe ser un objeto.");

			return null;
		}

		$expression = $rule['expression'] ?? null;
		if (!is_string($expression) || strlen($expression) > self::MAX_EXPRESSION_BYTES) {
			$form['header_rules']->addError("$label.expression debe ser texto de hasta " . self::MAX_EXPRESSION_BYTES . ' bytes.');

			return null;
		}

		if (($rule['action'] ?? null) !== 'set') {
			$form['header_rules']->addError("$label.action debe ser \"set\".");

			return null;
		}

		$parameters = $rule['action_parameters'] ?? null;
		$headers = is_array($parameters) ? ($parameters['headers'] ?? null) : null;
		if (!is_array($headers) || !array_is_list($headers)) {
			$form['header_rules']->addError("$label.action_parameters.headers debe ser una lista.");

			return null;
		}

		if (count($headers) > self::MAX_HEADERS_PER_RULE) {
			$form['header_rules']->addError("$label no puede tener más de " . self::MAX_HEADERS_PER_RULE . ' headers.');

			return null;
		}

		$normalizedHeaders = [];
		foreach ($headers as $position => $header) {
			$normalized = $this->parseHeader($form, "$label.action_parameters.headers[$position]", $header);
			if ($normalized === null) {
				return null;
			}

			$normalizedHeaders[] = $normalized;
		}

		return [
			'expression' => $expression,
			'action' => 'set',
			'action_parameters' => ['headers' => $normalizedHeaders],
		];
	}


	/**
	 * @return array<string, string>|null
	 */
	private function parseHeader(Form $form, string $label, mixed $header): ?array
	{
		if (!is_array($header)) {
			$form['header_rules']->addError("$label debe ser un objeto.");

			return null;
		}

		$name = $header['name'] ?? null;
		if (!is_string($name) || $name === '' || strlen($name) > self::MAX_HEADER_NAME_BYTES) {
			$form['header_rules']->addError("$label.name debe ser texto de hasta " . self::MAX_HEADER_NAME_BYTES . ' bytes.');

			return null;
		}

		$operation = $header['operation'] ?? 'set';
		if (!is_string($operation) || !in_array($operation, ['set', 'add', 'remove'], true)) {
			$form['header_rules']->addError("$label.operation debe ser set, add o remove.");

			return null;
		}

		$normalized = ['name' => $name, 'operation' => $operation];

		if ($operation !== 'remove') {
			$value = $header['value'] ?? null;
			if (!is_string($value) || strlen($value) > self::MAX_HEADER_VALUE_BYTES) {
				$form['header_rules']->addError("$label.value es obligatorio (máximo " . self::MAX_HEADER_VALUE_BYTES . ' bytes).');

				return null;
			}

			$normalized['value'] = $value;
		}

		return $normalized;
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function parsePreview(Form $form, ArrayHash $values): ?Preview
	{
		$valid = true;

		$rateLimit = null;
		try {
			$rateLimit = new RateLimit(
				$this->int($values, 'preview_rate_limit_max_requests'),
				$this->int($values, 'preview_rate_limit_window_seconds'),
			);
		} catch (InvalidArgumentException $e) {
			$form['preview_rate_limit_max_requests']->addError($e->getMessage());
			$valid = false;
		}

		$ttlDefault = $this->int($values, 'preview_ttl_default_seconds');
		$ttlMax = $this->int($values, 'preview_ttl_max_seconds');

		if ($ttlDefault < 1 || $ttlDefault > $ttlMax) {
			$form['preview_ttl_default_seconds']->addError('Debe estar entre 1 y el TTL máximo.');
			$valid = false;
		}

		if ($ttlMax > Preview::MAX_TTL_SECONDS) {
			$form['preview_ttl_max_seconds']->addError('No puede superar ' . Preview::MAX_TTL_SECONDS . ' segundos.');
			$valid = false;
		}

		$timeout = $this->int($values, 'preview_max_render_timeout_ms');
		if ($timeout < 1 || $timeout > Preview::MAX_RENDER_TIMEOUT_MS) {
			$form['preview_max_render_timeout_ms']->addError('Debe estar entre 1 y ' . Preview::MAX_RENDER_TIMEOUT_MS . ' ms.');
			$valid = false;
		}

		$bodyBytes = $this->int($values, 'preview_max_body_bytes');
		if ($bodyBytes < 1 || $bodyBytes > Preview::MAX_BODY_BYTES) {
			$form['preview_max_body_bytes']->addError('Debe estar entre 1 y ' . Preview::MAX_BODY_BYTES . ' bytes.');
			$valid = false;
		}

		$width = $this->int($values, 'preview_viewport_width');
		$height = $this->int($values, 'preview_viewport_height');
		foreach (['preview_viewport_width' => $width, 'preview_viewport_height' => $height] as $field => $side) {
			if ($side < 1 || $side > Preview::MAX_VIEWPORT_SIDE) {
				$control = $form[$field];
				if ($control instanceof BaseControl) {
					$control->addError('Cada lado debe estar entre 1 y ' . Preview::MAX_VIEWPORT_SIDE . '.');
				}
				$valid = false;
			}
		}

		if (!$valid) {
			return null;
		}

		return new Preview(
			enabled: $this->bool($values, 'preview_enabled'),
			metaEnabled: $this->bool($values, 'preview_meta_enabled'),
			capabilityMeta: $this->bool($values, 'preview_cap_meta'),
			capabilityContent: $this->bool($values, 'preview_cap_content'),
			capabilityScreenshot: $this->bool($values, 'preview_cap_screenshot'),
			capabilityPdf: $this->bool($values, 'preview_cap_pdf'),
			rateLimit: $rateLimit,
			ttlDefaultSeconds: $ttlDefault,
			ttlMaxSeconds: $ttlMax,
			maxRenderTimeoutMs: $timeout,
			maxBodyBytes: $bodyBytes,
			screenshotMaxFullPage: $this->bool($values, 'preview_max_full_page'),
			viewportWidth: $width,
			viewportHeight: $height,
		);
	}


	private function isInvalidFallbackUrl(string $url): bool
	{
		$parts = parse_url($url);
		if (!is_array($parts) || !isset($parts['scheme'], $parts['host'])) {
			return true;
		}

		return !in_array(strtolower($parts['scheme']), ['http', 'https'], true);
	}


	private function json(mixed $value): string
	{
		return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function str(ArrayHash $values, string $key): string
	{
		$value = $values[$key] ?? '';

		return is_string($value) ? $value : '';
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function int(ArrayHash $values, string $key): int
	{
		$value = $values[$key] ?? 0;

		return is_int($value) ? $value : (int) $value;
	}


	/**
	 * @param ArrayHash<mixed> $values
	 */
	private function bool(ArrayHash $values, string $key): bool
	{
		return (bool) ($values[$key] ?? false);
	}
}
