<?php
class SignupController extends GitHQController
{
	public function onFree()
	{
		$this->render("free.htm",array());
	}
}
