<?php declare(strict_types=1);

namespace App\SettingsManagement\Application;

use App\SettingsManagement\Domain\Settings;
use App\SettingsManagement\Domain\SettingsRepository;


final class UpdateSettingsHandler
{
	public function __construct(
		private readonly SettingsRepository $settingsRepository,
	) {}


	/**
	 * @param array<string, mixed> $patch
	 */
	public function handle(array $patch): Settings
	{
		return $this->settingsRepository->patch($patch);
	}
}
