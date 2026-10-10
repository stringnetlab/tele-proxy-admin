<?php declare(strict_types=1);

namespace App\Core;

use Nette;
use Nette\Application\Routers\RouteList;


final class RouterFactory
{
	use Nette\StaticClass;

	public static function createRouter(): RouteList
	{
		$router = new RouteList;

		$router->addRoute('_ok', 'Healthcheck:default');

		$authRouter = $router->withModule('Authentication');
		$authRouter->addRoute('login', 'Login:default');
		$authRouter->addRoute('login/google', 'Login:googleUrl');
		$authRouter->addRoute('login/google/callback', 'Login:googleCallback');
		$authRouter->addRoute('logout', 'Login:logout');

		$clientRouter = $router->withModule('ClientManagement');
		$clientRouter->addRoute('clients', 'Client:list');
		$clientRouter->addRoute('clients/create', 'Client:create');
		$clientRouter->addRoute('clients/export', 'Client:export');
		$clientRouter->addRoute('clients/import', 'Client:import');
		$clientRouter->addRoute('clients/<id>', 'Client:detail');
		$clientRouter->addRoute('clients/<id>/edit', 'Client:edit');
		$clientRouter->addRoute('clients/<id>/delete', 'Client:delete');
		$clientRouter->addRoute('clients/<id>/clone', 'Client:clone');
		$clientRouter->addRoute('clients/<id>/rotate-token', 'Client:rotateToken');
		$clientRouter->addRoute('clients/<id>/rotate-id', 'Client:rotateCryptId');

		$adminRouter = $router->withModule('AdminManagement');
		$adminRouter->addRoute('admins', 'Admin:list');
		$adminRouter->addRoute('admins/create', 'Admin:create');
		$adminRouter->addRoute('admins/<email>', 'Admin:detail');
		$adminRouter->addRoute('admins/<email>/toggle', 'Admin:toggle');
		$adminRouter->addRoute('admins/<email>/delete', 'Admin:delete');

		$router->addRoute('<presenter>/<action>[/<id>]', 'ClientManagement:Client:list');

		return $router;
	}
}
