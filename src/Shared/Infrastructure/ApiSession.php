<?php declare(strict_types=1);

namespace App\Shared\Infrastructure;


/**
 * Cookie de sesión del API vigente durante la petición actual. La fija BasePresenter tras
 * validar la sesión del panel; nunca se serializa ni se expone al navegador.
 */
final class ApiSession
{
	private ?string $cookie = null;


	public function setCookie(?string $cookie): void
	{
		$this->cookie = $cookie;
	}


	public function cookie(): ?string
	{
		return $this->cookie;
	}
}
