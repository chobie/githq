<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class AccountController extends GitHQ\Bundle\AbstractController
{
	public function onNew()
	{
		$user = $this->getUser();
		$request = $this->get('request');
		
		if ($request->isPost()) {
			if (!User::getIdByEmail($request->get("email")) && !User::getIdByNickname($request->get("name")) ) {
				/** for now, organization uses `org` as prefix */
				$organization = new User("org." . User::getNextId());
				$organization->setNickname($request->get("name"));
				$organization->setEmail($request->get("email"));
				$organization->setUserAsOrganizer();
				$organization->addMember($user->getKey());
				$organization->create();
				
				return new RedirectResponse($this->get('application.url'));
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