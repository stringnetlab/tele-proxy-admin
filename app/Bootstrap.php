<?php declare(strict_types=1);

namespace App;

use Nette;
use Nette\Bootstrap\Configurator;


class Bootstrap
{
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
		$this->initializeEnvironment();
		$this->loadEnvVars();
		$this->setupContainer();
		return $this->configurator->createContainer();
	}


	public function initializeEnvironment(): void
	{
		$this->configurator->enableTracy($this->rootDir . '/log');

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

		$lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $line) {
			$line = trim($line);
			if ($line === '' || str_starts_with($line, '#')) {
				continue;
			}
			if (!str_contains($line, '=')) {
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
	}
}
