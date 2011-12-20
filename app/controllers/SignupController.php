<?php
class SignupController extends GitHQ\Bundle\AbstractController
{
	public function onFree()
	{
		if(!$this->snapi->getUser()) {
			header("Location: /connect");
			exit;
		}
		
		$this->render("free.htm");
	}
}
