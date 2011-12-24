<?php
class SignupController extends GitHQ\Bundle\AbstractController
{
	public function onFree()
	{
		if(!$this->get('facebook')->getUser()) {
			header("Location: /connect");
			exit;
		}
		
		$this->render("free.htm");
	}
}
