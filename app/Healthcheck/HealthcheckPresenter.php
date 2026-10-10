<?php declare(strict_types=1);

namespace App\Healthcheck;

use Nette\Application\UI\Presenter;


final class HealthcheckPresenter extends Presenter
{
	public function actionDefault(): void
	{
		$this->sendJson(['status' => 'ok', 'timestamp' => date('c')]);
	}
}
