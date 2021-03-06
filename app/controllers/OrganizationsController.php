<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class OrganizationsController extends GitHQ\Bundle\AbstractController
{
	public $view = "OrganizationsView";
	
	/**
	* (non-PHPdoc)
	* @see UIKit\Framework\HTTPFoundation\Controller.ApplicationController::onDefault()
	* @param string $organization organization name
	* @return HTTPResponse $response
	*/
	public function onDefault($organization)
	{
		$organization = User::getByNickname($organization);
		$timeline     = Activity::getGlobalTimeline();

		return $this->getDefaultView()->prepareResponse(array(
			'organization'  => $organization,
			'timeline'      => $timeline,
		));
	}

	/**
	* (non-PHPdoc)
	* @see UIKit\Framework\HTTPFoundation\Controller.ApplicationController::onDefault()
	* @param string $organization organization name
	*/
	public function onNew($organization)
	{
		$request = $this->get('request');
		
		if ($request->isPost()) {
			$project_name = $request->get('project_name');
			$description  = $request->get('description');
			$homepage_url = $request->get('homepage_url');
			
			$user = User::fetchLocked(User::getIdByNickname($organization));
			$repo = new Repository($project_name);
			$id   = $user->getNextRepositoryId();

			$repo->setId($id);
			$repo->setDescription($description);
			$repo->setHomepageUrl($homepage_url);
				
			if ($repo->create($user->getKey())) {
				$user->addRepository($repo);
				if ($request->get('visibility') == 1) {
					$repo->setPrivate();
				} else {
					$this->get('event')->emit(new UIKit\Framework\Event('repository.new',array($user,$repo)));						
				}
				$repo->watch($user,$user);
			}
			$user->save();
			
			return new RedirectResponse($this->generateResponse('top'));
		} else {
			return $this->getDefaultView()
						->setTemplate("new.htm")
						->prepareResponse(array(
							'organization' => $organization,
			));
				
		}
	}
}