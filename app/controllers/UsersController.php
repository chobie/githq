<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class UsersController extends GitHQ\Bundle\AbstractController
{
	/**
	 * create new user blob
	 * 
	 * @return RedirectResponse $response
	 */
	public function onDefault()
	{
		$request = $this->getRequest();
		
		$reponse = new RedirectResponse();
		
		if ($this->getRequest()->isPost()) {
			if(!$user_id = $this->get('facebook')->getUser()) {
				/* could not obtain the user id. redirect fb. */
				$response->setLocation($this->generateUrl('facebook.connect'));
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
						$response->setLocation($this->generateUrl('top'));
						
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
