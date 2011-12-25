<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class UsersController extends GitHQ\Bundle\AbstractController
{
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework\HTTPFoundation\Controller.ApplicationController::onDefault()
	 */
	public function onDefault()
	{
		$request = $this->getRequest();
		
		$reponse = new RedirectResponse();
		
		if ($this->getRequest()->isPost()) {
			if(!$user_id = $this->get('facebook')->getUser()) {
				$response->setLocation($this->get('application.url') . "/connect");
				return $response;
			}

			try {
				if (!User::getIdByNickname($request->get('username')) &&
					!User::getIdByEmail($request->get('email'))) {

					$user = new User($user_id);
					$user->setNickname($request->get('username'));
					$user->setEmail($request->get('email'));
					$user->setPassword($request->get('password'));

					if ($user->create()) {
						$response->setLocation($this->get('application.url'));
						
						return $response;
					} else {
						throw new \Exception("could not create user.");
					}
				} else {
					echo "B.specified user name exists. please choose another one";
				}
			} catch (\Exception $e) {
				echo "E.specified user name exists. please choose another one";
			}
		}
	}
}
