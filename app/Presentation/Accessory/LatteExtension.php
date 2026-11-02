<?php declare(strict_types=1);

namespace App\Presentation\Accessory;

use Latte\Extension;


final class LatteExtension extends Extension
{
	/**
	 * @return array<string, callable(mixed...): mixed>
	 */
	public function getFilters(): array
	{
		return [];
	}


	/**
	 * @return array<string, callable(mixed...): mixed>
	 */
	public function getFunctions(): array
	{
		return [];
	}
}
