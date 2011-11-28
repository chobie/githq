<?php
class RootController extends UIkit\Framework\UIAppController
{
	public function onDefault()
	{
		$this->render("index.htm",array());
	}

	public function onSession()
	{
		if($this->is_post()) {
			$user = User::get($_REQUEST['username'],"user");
			var_dump($user);
			if ($user && $user->checkPassword($_REQUEST['password'])) {
				echo "OK";
			}
		} else {
			//header("Location: http://githq.org/");
		}
	}
}
