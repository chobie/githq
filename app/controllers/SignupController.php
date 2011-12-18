<?php
class SignupController extends GitHQController
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
