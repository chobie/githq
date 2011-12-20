<?php
class AccountController extends GitHQ\Bundle\AbstractController
{
	public function onNew()
	{
		$user = $this->getUser();
		if ($this->getRequest()->isPost()) {
			
			if (!User::getIdByEmail($this->getRequest()->get("email")) && !User::getIdByNickname($this->getRequest()->get("name")) ) {
				$organization = new User("org." . User::getNextId(),"user");
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