<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class RootController extends GitHQ\Bundle\AbstractController
{	
	public function onDefault()
	{
		$this->get("logger")->addDebug("Hey");
		
		$user = $this->getUser();
		$data = null;
		$organizations = null;
				
		$timeline = Activity::getGlobalTimeline();
		if ($user) {
			$organizations = $user->getJoinedOrganizations();
		}
		
		$this->render("index.htm",array(
			'user'          =>$user,
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
		if($this->getRequest()->isPost()) {
			$user = User::getByNickname($_REQUEST['username']);
			if ($user && $user->checkPassword($_REQUEST['password'])) {
				$_SESSION['user'] = $user;
				return new RedirectResponse($this->get('application.url'));
			}
		} else {
			return new RedirectResponse($this->get('application.url'));
		}
	}
	
	public function onLogout()
	{
		$_SESSION = array();
		return new RedirectResponse($this->get('application.url'));
	}
	
	public function onAccount()
	{
		$user = $this->getUser();
		$profile = $user->getProfile();
		
		if ($this->getRequest()->isPost()) {
			$user = User::fetchLocked($user->getKey());
			if (isset($_REQUEST['public_key'])) {
				if (is_array($_REQUEST['key'])) {
					foreach ($_REQUEST['key'] as $key) {
						$pub = new PublicKey($key);
						if ($pub->verify()) {
							$pub->setTitle($_REQUEST['title']);
							$user->addPublicKey($pub);
						}
					}
				} else {
					$pub = new PublicKey($_REQUEST['key']);
					if ($pub->verify()) {
						$pub->setTitle($_REQUEST['title']);
						$user->addPublicKey($pub);
					}else {
						throw new Exception("could not verify");
					}
					
				}
			} else if (isset($_REQUEST['del_public_key'])) {
				$user->removePublicKey($_REQUEST['offset']);				
			} else if (isset($_REQUEST['account'])) {
				$user->setEmail($_REQUEST['email']);
			} else {
				$profile = $user->getProfile();
				$profile->setName($_REQUEST['name']);
				$profile->setEmail($_REQUEST['email']);
				$profile->setLocation($_REQUEST['location']);
				$profile->setCompany($_REQUEST['company']);
				$profile->setHomepage($_REQUEST['homepage']);
			}

			
			$user->save();
			$_SESSION['user'] = $user;
		}
		$this->render("account.htm",array(
			"user"=>     $user,
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
				$_SESSION['user'] = $user;
				$response->setLocation($this->get('application.url'));
			} else {
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
	 * @Controller(newtype=true)
	 */
	public function onUser($user)
	{
		$owner    = User::getByNickname($user);
		$user     = $this->getUser();
		if (!$owner) {
			return $this->on404();
		}
		
		$timeline = Activity::getTimelineByUserId($owner->getKey());
		$this->render("user.htm",array(
			'owner'    => $owner,
			'user'     => $user,
			'timeline' => $timeline,
		));
	}

	/**
	 * show githq purposes.
	 */
	public function onAbout()
	{
		$user = $this->getUser();
		$this->render('about.htm',array(
			'user'=>$user
		));
	}

}
