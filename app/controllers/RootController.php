<?php
class RootController extends GitHQController
{
	
	public function onDefault($params =  array())
	{
		$user = $this->getUser();
		
		if ($this->getRequest()->isPost()) {
			$owner = User::get(UserPointer::getIdByNickname($_REQUEST['user']),"user");
			if (!$user->getRepository($_REQUEST['repository'])) {
				$origin = $owner->getRepository($_REQUEST['repository']);
				
				$user = User::fetchLocked($_SESSION['user']->getKey(),"user");
				if ($repo = $origin->fork($owner, $user)) {
					$user->addRepository($repo);
					$user->save();

					$_SESSION['user'] = $user;
				}
				$this->render("fork.htm");
				exit;
			} else {
				throw new \Exception("could not fork repository.");
			}
		}
		
		if (isset($params['controller'])) {
			$owner = User::get(UserPointer::getIdByNickname($params['controller.orig']),"user");
			try{
				$repo = new \Git\Repository("/home/git/repositories/{$params['controller.orig']}/{$params['action.orig']}.git");
				$ref = $repo->lookupRef("refs/heads/master");
				$commit = $repo->getCommit($ref->getId());
				$tree = $commit->getTree();
			
			}catch(\InvalidArgumentException $e) {
				$commit = null;
				$tree = null;
			}
			$this->render("repository.htm",array(
				'user' => $user,
				'owner'=> $owner,
				'repository'=> $owner->getRepository($params['action.orig']),
				'commit' => $commit,
				'tree' => $tree,
			));
		} else {
			$this->render("index.htm",array('user'=>$user));
		}
	}
	
	/**
	 * resolve file name of inside git repository.
	 * 
	 * @param \Git\Tree $tree
	 * @param string $name filename or path
	 * @return \Git\Object $object
	 */
	protected function resolve_filename($tree,$name)
	{
		$list = explode("/",$name);
		$cnt = count($list);

		$i = 1;
		while ($fname = array_shift($list)) {
			foreach ($tree->getIterator() as $entry) {
				if ($entry->name == $fname) {
					if ($i < $cnt && $entry->isTree()) {
						return $this->resolve_filename($entry->toObject(),join("/",$list));
					} else {
						return $entry->toObject();
					}
				}
			}
			$i++;
		}

		return null;
	}
	
	public function onBlob($params)
	{
		$user = $this->getUser();
		
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repo = new \Git\Repository("/home/git/repositories/{$params['user']}/{$params['repository']}.git");
		$refm = new \Git\Reference\Manager($repo);
		$branches = $refm->getList();

		$ref = $repo->lookupRef("refs/heads/master");
		$commit = $repo->getCommit($ref->getId());
		$current_path = '';
		if ($params['path']) {
			$paths = explode('/',$params['path']);
			if(count($paths)> 1) {
				$current_path = join('/',$paths) . '/';
			} else{
				$current_path = $params['path'] . '/';
			}
			$tree = $commit->getTree();
			$blob = $this->resolve_filename($tree,$params['path']);
			$tree = $this->resolve_filename($tree,dirname($params['path']));
		}
		
		$data = Albino::colorize($blob->data,pathinfo($params['path'],\PATHINFO_EXTENSION));
		if (!$data) {
			$data = "<pre>" . htmlspecialchars($blob->data) . "</pre>";
		}

		$this->render("repository.htm",array(
			'user'         => $user,
			'owner'        => $owner,
			'repository'   => $owner->getRepository($params['repository']),
			'commit'       => $commit,
			'tree'         => $tree,
			'blob'         => $blob,
			'data'         => $data,
			'current_path' => dirname($params['path']) . '/',
			'path'         => $params['path']
		));
	}

	public function onTree($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repo = new \Git\Repository("/home/git/repositories/{$params['user']}/{$params['repository']}.git");
		$ref = $repo->lookupRef("refs/heads/master");
		$commit = $repo->getCommit($ref->getId());
		$current_path = '';
		if ($params['path']) {
			$paths = explode('/',$params['path']);
			if(count($paths)> 1) {
				$current_path = join('/',$paths) . '/';
			} else{
				$current_path = $params['path'] . '/';
			}
			$tree = $commit->getTree();
			$tree = $this->resolve_filename($tree,$params['path']);
		}
		$this->render("repository.htm",array(
			'user'         => $user,
			'owner'        => $owner,
			'repository'   => $owner->getRepository($params['repository']),
			'commit'       => $commit,
			'tree'         => $tree,
			'current_path' => $current_path
		));
	}


	public function onSession()
	{
		if($this->getRequest()->isPost()) {
			$user = User::get(UserPointer::getIdByNickname($_REQUEST['username']),"user");
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
	
	public function onAccount()
	{
		$user = $this->getUser();
		$profile = $user->getProfile();
		if ($this->getRequest()->isPost()) {
			$user = User::fetchLocked($user->getKey(),'user');
			$profile = $user->getProfile();
			$profile->setName($_REQUEST['name']);
			$profile->setEmail($_REQUEST['email']);
			$user->save();
			$_SESSION['user'] = $user;
		}
		$this->render("account.htm",array(
			"user"=>     $user,
			"profile" => $profile
		));
	}
	
	public function onCommits($params)
	{
		$user = $_SESSION['user'];
		$repo = new \Git\Repository("/home/git/repositories/{$params['user']}/{$params['repository']}.git");
		$ref = $repo->lookupRef("refs/heads/master");
		$commit = $repo->getCommit($ref->getId());
		$walker = $repo->getWalker();
		$walker->push($commit->getId());
		$i=0;
		$commits = array();
		while($i < 20 && $tmp = $walker->next()) {
			$commits[] = $tmp;
			$i++;
		}
		$this->render("commits.htm",array(
			'user'=> $user,
			'repository'=> $user->getRepository($params['repository']),
			"commits" => $commits
		));
	}
}
