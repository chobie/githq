<?php
class UsersController extends UIkit\Framework\UIAppController
{
	public function onDefault()
	{
		if ($this->getRequest()->isPost()) {
			try {
				if (!UserPointer::getIdByNickname($_REQUEST['username'])) {
					$id = UserPointer::getNextId();
					$user = new User($id);
					$user->setNickname($_REQUEST['username']);
					$user->setEmail($_REQUEST['email']);
					$user->setPassword($_REQUEST['password']);
					if ($user->create()) {
						header("Location: http://githq.org/");
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
