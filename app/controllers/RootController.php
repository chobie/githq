<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class RootController extends GitHQ\Bundle\AbstractController
{	
	public function onDefault()
	{
		$this->get("logger")->addDebug("Hey");
		
		$organizations = null;
				
		$timeline = Activity::getGlobalTimeline();
		if ($this->getUser()) {
			$organizations = $this->getUser()->getJoinedOrganizations();
		}
		
		$this->render("index.htm",array(
			'timeline'      => $timeline,
			'organizations' => $organizations,
		));
	}
		

	/**
	 * for builtin authentication.
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
		
		return new RedirectResponse($this->get('application.url'));
	}
	
	public function onLogout()
	{
		$_SESSION = array();
		return new RedirectResponse($this->get('application.url'));
	}
	
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
		
		$this->render("account.htm",array(
			"profile" => $profile
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
		
		if ($user_id) {
			if ($user = User::get($user_id)) {
				/* login succeeded */
				$_SESSION['user'] = $user;
				$response->setLocation($this->get('application.url'));
			} else {
				/* first time. redirect registration page */
				$response->setLocation($this->get('application.url') . '/signup/free');
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
		
		$timeline = Activity::getTimelineByUserId($owner->getKey());
		$this->render("user.htm",array(
			'owner'    => $owner,
			'timeline' => $timeline,
		));
	}

	/**
	 * show githq purposes.
	 */
	public function onAbout()
	{
		$user = $this->getUser();
		$this->render('about.htm',array());
	}

}
