<?php
class AccountController extends GitHQController
{
	public function onNew()
	{
		$user = $this->getUser();
		if ($this->getRequest()->isPost()) {
			
			if (!UserPointer::getIdByEmail($this->getRequest()->get("email")) && !UserPointer::getIdByNickname($this->getRequest()->get("name")) ) {
				$organization = new User("org." . UserPointer::getNextId(),"user");
				$organization->setNickname($this->getRequest()->get("name"));
				$organization->setEmail($this->getRequest()->get("email"));
				$organization->setUserAsOrganizer();
				$organization->addMember($user->getKey());
				$organization->create();
				header("Location: https://githq.org/");
			}
		}
	}
	public function onOrganizations()
	{
		$user = $this->getUser();
		
		return $this->render("organizations.htm",array(
			'user' => $user, 
		));
		
	}
}