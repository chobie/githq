<?php
class RootController extends UIkit\Framework\UIAppController
{
	protected function getUser()
	{
		if (isset($_SESSION['user']) && $_SESSION['user'] instanceof User) {
			return $_SESSION['user'];
		} else {
			return null;
		}
	}
	
	public function onDefault($params =  array())
	{
		$user = $this->getUser();
		
		if(isset($_SESSION['user'])) {
			echo "you logged in";
		}
		
		if (isset($params['controller'])) {
			$repo = new \Git\Repository("/tmp/GitHQ/.git");
			$ref = $repo->lookupRef("refs/heads/develop");
			$commit = $repo->getCommit($ref->getId());
			$this->render("repository.htm",array(
				'user'=> $user,
				'repository'=> $user->getRepository($params['action.orig']),
				'commit' => $commit,
			));
		} else {
			$this->render("index.htm",array('user'=>$user));
		}
	}

	public function onTree($params)
	{
		var_dump($params);
		$user = $_SESSION['user'];
		$repo = new \Git\Repository("/tmp/GitHQ/.git");
		$ref = $repo->lookupRef("refs/heads/develop");
		$commit = $repo->getCommit($ref->getId());
		$this->render("repository.htm",array(
			'user'=> $user,
			'repository'=> $user->getRepository($params['action.orig']),
			'commit' => $commit,
		));
	}


	public function onSession()
	{
		if($this->is_post()) {
			$user = User::get($_REQUEST['username'],"user");
			if ($user && $user->checkPassword($_REQUEST['password'])) {
				$_SESSION['user'] = $user;
				header("Location: http://githq.org/");
			}
		} else {
			header("Location: http://githq.org/");
		}
	}
	
	public function onLogout()
	{
		$_SESSION = array();
		header("Location: http://githq.org/");
	}
}
