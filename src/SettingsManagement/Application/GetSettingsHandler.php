<?php declare(strict_types=1);

namespace App\SettingsManagement\Application;

use App\SettingsManagement\Domain\Settings;
use App\SettingsManagement\Domain\SettingsRepository;


final class GetSettingsHandler
{
	public function __construct(
		private readonly SettingsRepository $settingsRepository,
	) {}


	public function handle(): Settings
	{
		return $this->settingsRepository->get();
	}
}
