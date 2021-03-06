<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class RootController extends GitHQ\Bundle\AbstractController
{	
	public $view = "RootView";
		
	/**
	 * show githq landing page.
	 * 
	 * @return HTTPResponse $response
	 */
	public function onDefault()
	{
		$organizations = null;
		if ($this->getUser()) {
			$organizations = $this->getUser()->getJoinedOrganizations();
		}
		
		return $this->getDefaultView()->prepareResponse(array(
			'timeline'      => Activity::getGlobalTimeline(),
			'organizations' => $organizations,
		));
	}
	
	/**
	 * for builtin authentication.
	 * 
	 * we are using Facebook as a authentication system.
	 * so we don't use this method
	 * 
	 * @deprecated
	 */
	public function onSession()
	{
		$request = $this->get('request');
		if($request->isPost()) {
			$user = User::getByNickname($request->get('username'));
			if ($user && $user->checkPassword($request->get('password'))) {
				$_SESSION['user'] = $user;
			}
		}
		
		return new RedirectResponse($this->generateUrl('top'));
	}
	
	/**
	 * logout githq.
	 * 
	 * @return RedirectResponse $response 
	 */
	public function onLogout()
	{
		$_SESSION = array();
		return new RedirectResponse($this->generateUrl('top'));
	}
	
	/**
	 * show account setting page
	 * 
	 * @return HTTPResponse $response
	 * @throws Exception
	 */
	public function onAccount()
	{
		$user = $this->getUser();
		$request = $this->get('request');
		$profile = $user->getProfile();
		
		if ($request->isPost()) {
			$user = User::fetchLocked($user->getKey());
			
			if ($request->has('public_key')) {
				/* update public key */
				if (is_array($request->get('key'))) {
					foreach ($request->get('key') as $key) {
						$pub = new PublicKey($key);
						if ($pub->verify()) {
							$pub->setTitle($request->get('title'));
							$user->addPublicKey($pub);
						}
					}
				} else {
					$pub = new PublicKey($request->get('key'));
					if ($pub->verify()) {
						$pub->setTitle($request->get('title'));
						$user->addPublicKey($pub);
					}else {
						throw new Exception("could not verify");
					}
				}
			} else if ($request->has('del_public_key')) {
				/* remove specified public key */
				$user->removePublicKey($request->get('offset'));				
			} else if ($request->has('acoount')) {
				/* update account email */
				$user->setEmail($request->get('email'));
			} else {
				/* update profile */
				$profile = $user->getProfile();
				$profile->setName($request->get('name'));
				$profile->setEmail($request->get('email'));
				$profile->setLocation($request->get('location'));
				$profile->setCompany($request->get('company'));
				$profile->setHomepage($request->get('homepage'));
			}
			
			$user->save();
			$_SESSION['user'] = $user;
		}

		$view = $this->getDefaultView();
		$view->setTemplate("account.htm");
		return $view->prepareResponse(array(
			"profile" => $profile,
		));
	}
	
	/**
	 * check user has facebook session.
	 * 
	 * @return RedirectResponse $response
	 */
	public function onConnect()
	{
		$user_id  = $this->get('facebook')->getUser();
		$response = new \UIKit\Framework\HTTPFoundation\Response\RedirectResponse();
		$response->setLocation($this->generateUrl("top"));
		
		if ($user_id) {
			if ($user = User::get($user_id)) {
				/* login succeeded */
				$_SESSION['user'] = $user;
			} else {
				/* first time. redirect registration page */
				$response->setLocation($this->generateUrl('registration'));
			}
		} else {
			$response->setLocation($this->get('facebook')->getLoginUrl());
		}
		
		return $response;
	}
		
	/**
	 * show public user profile and his/her repositories.
	 * 
	 */
	public function onUser($user)
	{
		$owner = User::getByNickname($user);
		if (!$owner) {
			return $this->on404();
		}
		
		$view = $this->getDefaultView();
		$view->setTemplate("user.htm");
		return $view->prepareResponse(array(
			'owner'    => $owner,
			'timeline' => Activity::getTimelineByUserId($owner->getKey()),
		));
	}

	/**
	 * show githq purposes.
	 */
	public function onAbout()
	{
		return $this->getDefaultView()
					->setTemplate("about.htm")
					->prepareResponse();
	}

}
