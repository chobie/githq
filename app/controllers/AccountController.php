<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class AccountController extends GitHQ\Bundle\AbstractController
{
	public $view = "AccountView";
	
	/**
	 * registeration page for new organization
	 * 
	 * @return RedirectResponse $response
	 */
	public function onNew()
	{
		$user = $this->getUser();
		$request = $this->get('request');
		
		if ($request->isPost()) {
			if (!User::getIdByEmail($request->get("email")) &&
				!User::getIdByNickname($request->get("name")) ) {

				/** for now, organization uses `org` as prefix */
				$organization = new User("org." . User::getNextId());
				$organization->setNickname($request->get("name"));
				$organization->setEmail($request->get("email"));
				$organization->setUserAsOrganizer();
				$organization->addMember($user->getKey());
				$organization->create();
				
				return new RedirectResponse($this->generateUrl('top'));
			}
		}
	}
	
	public function onOrganizations()
	{
		return $this->getDefaultView()->setTemplate("organizations.htm")->prepareResponse();
	}
}