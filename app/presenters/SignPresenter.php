<?php

namespace App\Presenters;

use Nette,
	App\Model;


/**
 * Sign in/out presenters.
 */
class SignPresenter extends BasePresenter
{


	public function actionOut()
	{
		$this->getUser()->logout();
		$this->flashMessage('Boli ste odhlasený.');
		$this->redirect('Homepage:default');
	}

}
