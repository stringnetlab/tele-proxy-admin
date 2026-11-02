<?php declare(strict_types=1);

namespace App;

use Mnohosten\TracyMarkdown\TracyMarkdownExtension;
use Nette;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
	/** env name => [neon parameter, default used when the variable is absent; null means required] */
	private const Env = [
		'APP_ENV' => ['appEnv', 'production'],
		'PUBLIC_API_URL' => ['apiUrl', null],
		'COUCHDB_URL' => ['couchDbUrl', null],
		'COUCHDB_DATABASE' => ['couchDbDatabase', 'teleproxy_admin'],
		'VALKEY_URL' => ['valkeyDsn', null], // el nombre "Dsn" hace que el MCP Inspector enmascare la contraseña embebida en la URL
		'SESSION_SECRET' => ['sessionSecret', null],
		'TRUSTED_PROXIES' => ['trustedProxies', ''],
		'LOGIN_RATE_LIMIT_REQUESTS' => ['loginRateLimitRequests', '5'],
		'LOGIN_RATE_LIMIT_WINDOW_SECONDS' => ['loginRateLimitWindowSeconds', '60'],
		'TZ_DISPLAY' => ['tzDisplay', 'America/Lima'],
	];

	private readonly Configurator $configurator;
	private readonly string $rootDir;


	public function __construct()
	{
		$this->rootDir = dirname(__DIR__);
		$this->configurator = new Configurator;
		$this->configurator->setTempDirectory($this->rootDir . '/temp');
	}


	public function bootWebApplication(): Nette\DI\Container
	{
		return $this->bootConfigurator()->createContainer();
	}


	public function bootConfigurator(): Configurator
	{
		$this->loadEnvVars();
		$this->initializeEnvironment();
		$this->setupContainer();
		return $this->configurator;
	}


	public function initializeEnvironment(): void
	{
		$this->configurator->setDebugMode(($this->readEnv('APP_ENV') ?? 'production') !== 'production');
		$this->configurator->enableTracy($this->rootDir . '/log');
		TracyMarkdownExtension::register();

		$this->configurator->createRobotLoader()
			->addDirectory(__DIR__)
			->addDirectory($this->rootDir . '/src')
			->register();
	}


	private function loadEnvVars(): void
	{
		$envFile = $this->rootDir . '/.env';
		if (!file_exists($envFile)) {
			return;
		}

		$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
				continue;
			}
			[$name, $value] = explode('=', $line, 2);
			$name = trim($name);
			$value = trim($value);
			if (!array_key_exists($name, $_ENV)) {
				$_ENV[$name] = $value;
				putenv("$name=$value");
			}
		}
	}


	private function setupContainer(): void
	{
		$configDir = $this->rootDir . '/config';
		$this->configurator->addConfig($configDir . '/common.neon');
		$this->configurator->addConfig($configDir . '/services.neon');
		$this->configurator->addConfig(['parameters' => $this->readEnvParameters()]);
	}


	/**
	 * @return array<string, string>
	 */
	private function readEnvParameters(): array
	{
		$parameters = [];
		$missing = [];
		foreach (self::Env as $envName => [$parameter, $default]) {
			$value = $this->readEnv($envName);
			if ($value === null) {
				if ($default === null) {
					$missing[] = $envName;
				} else {
					$parameters[$parameter] = $default;
				}
			} else {
				$parameters[$parameter] = $value;
			}
		}

		if ($missing !== []) {
			throw new Nette\InvalidStateException(
				'Faltan variables de entorno obligatorias: ' . implode(', ', $missing) . '. '
				. 'Copia .env.example a .env o define los valores en el entorno de Docker.',
			);
		}

		return $parameters;
	}


	private function readEnv(string $name): ?string
	{
		$value = $_ENV[$name] ?? $_SERVER[$name] ?? getenv($name);
		return is_string($value) && $value !== '' ? $value : null;
	}
}
