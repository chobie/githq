<?php
class UsersController extends UIkit\Framework\UIAppController
{
	public function onDefault()
	{
		if ($this->is_post()) {
			$user = new User($_REQUEST['username']);
			$user->setEmail($_REQUEST['email']);
			$user->setPassword($_REQUEST['password']);
			if ($user->create()) {
				header("Location: http://githq.org/");
			} else {
				echo "can't create";
			}
		}
	}
}
