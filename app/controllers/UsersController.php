<?php
class UsersController extends GitHQ\Bundle\AbstractController
{
	public function onDefault()
	{
		$request = $this->getRequest();
		
		if ($this->getRequest()->isPost()) {
			if(!$user_id = $this->snapi->getUser()) {
				header("Location: /connect");
				exit;
			}

			try {
				if (!User::getIdByNickname($request->get('username')) && !User::getIdByEmail($request->get('email'))) {
					$user = new User($user_id);
					$user->setNickname($request->get('username'));
					$user->setEmail($request->get('email'));
					$user->setPassword($request->get('password'));

					if ($user->create()) {
						header("Location: http://githq.org/");
						return;
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
