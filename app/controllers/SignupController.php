<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class SignupController extends GitHQ\Bundle\AbstractController
{
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework\HTTPFoundation\Controller.ApplicationController::onDefault()
	 */
	public function onFree()
	{
		if(!$this->get('facebook')->getUser()) {
			return new RedirectResponse($this->get('application.url') . '/connect');
		}
		
		$this->render("free.htm");
	}
}
