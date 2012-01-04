<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class SignupController extends GitHQ\Bundle\AbstractController
{
	public $view = "RootView";

	/**
	 * show registration page
	 * 
	 * @return HTTPResponse $response
	 */
	public function onFree()
	{
		if(!$this->get('facebook')->getUser()) {
			return new RedirectResponse($this->generateUrl('facebook.connect'));
		}
		
		return $this->getDefaultView()
					->setTemplate("/signup/free.htm")
					->prepareResponse();
	}
}
