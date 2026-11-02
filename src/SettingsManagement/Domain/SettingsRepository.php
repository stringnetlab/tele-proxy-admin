<?php declare(strict_types=1);

namespace App\SettingsManagement\Domain;


interface SettingsRepository
{
	public function get(): Settings;


	/**
	 * @param array<string, mixed> $patch
	 */
	public function patch(array $patch): Settings;
}
