<?php
class RootController extends GitHQController
{
	
	public function onDefault($params =  array())
	{
		$data = null;
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
			$repository = $owner->getRepository($params['action.orig']);
			if (!$repository->hasPermission($owner, $user)) {
				echo "<h1>403 Forbidden</h1>";
				return;
			}
			
			try{
				$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
				$ref = $repo->lookupRef("refs/heads/master");
				$commit = $repo->getCommit($ref->getId());
				$tree = $commit->getTree();
				$blob = $this->resolve_filename($tree,"README.md");
				$data = null;
				if ($blob) {
					$sd = new \Sundown($blob->data);
					$data = $sd->to_html();
				}
				
			}catch(\InvalidArgumentException $e) {
				$commit = null;
				$tree = null;
			}
			$this->render("repository.htm",array(
				'user' => $user,
				'owner'=> $owner,
				'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(),$owner->getRepository($params['action.orig'])->getId()),	
				'repository'=> $owner->getRepository($params['action.orig']),
				'commit' => $commit,
				'tree' => $tree,
				'data' => $data,
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
		$repository = $owner->getRepository($params['repository']);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
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
		
		$ext = pathinfo($params['path'],\PATHINFO_EXTENSION);
		switch ($ext) {
			case 'mardkwon':
			case 'md':
				$sd = new \Sundown($blob->data);
				$data = $sd->to_html();
				break;
			default:
				$data = Albino::colorize($blob->data,$ext);
		}
		if (!$data) {
			$data = "<pre>" . htmlspecialchars($blob->data) . "</pre>";
		}

		$this->render("repository.htm",array(
			'user'         => $user,
			'owner'        => $owner,
			'repository'   => $repository,
			'commit'       => $commit,
			'tree'         => $tree,
			'blob'         => $blob,
			'data'         => $data,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
			'current_path' => dirname($params['path']) . '/',
			'path'         => $params['path']
		));
	}

	public function onTree($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
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
		$parent_dir = dirname($current_path);
		
		$this->render("repository.htm",array(
			'user'         => $user,
			'owner'        => $owner,
			'repository'   => $repository,
			'commit'       => $commit,
			'tree'         => $tree,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
			'current_path' => $current_path,
			'parent_dir'   => $parent_dir,
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
	
	public function onCommit($params)
	{
		$owner = User::get(UserPointer::getIdByNickname($params['user']),"user");
		$user = $this->getUser();
		$repository = $owner->getRepository($params['repository']);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$stat = `GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git log -p {$params['commit']} -n1`;
		$struct = Text\Diff\Parser::parse($stat);
		
		$ref = $repo->lookupRef("refs/heads/master");
		$commit = $repo->getCommit($params['commit']);
		
		$this->render("commit.htm",array(
					'user'=> $user,
					'owner' => $owner,
					'repository'=> $owner->getRepository($params['repository']),
					"commit" => $commit,
					"diff" => $struct,
		));
	}
	
	public function onCommits($params)
	{
		$owner = User::get(UserPointer::getIdByNickname($params['user']),"user");
		$user = $this->getUser();
		$repository = $owner->getRepository($params['repository']);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
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
			'owner' => $owner,
			'repository'=> $owner->getRepository($params['repository']),
			"commits" => $commits
		));
	}
	
	public function onConnect()
	{
		$user_id = $this->snapi->getUser();
		if ($user_id) {
			if ($user = User::get($user_id,'user')) {
				$_SESSION['user'] = $user;
				header("Location: http://githq.org/");
			} else {
				header("Location: http://githq.org/signup/free");
			}
		} else {
			header("Location: " . $this->snapi->getLoginUrl());
		}
	}
	
	public function onUser($params)
	{
		$owner = User::get(UserPointer::getIdByNickname($params['user']),"user");
		$user = $this->getUser();
		$this->render("user.htm",array(
			'owner' => $owner,
			'user' => $user,
		));
	}
	
	public function onBlame($params)
	{
		$user = $this->getUser();
		
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
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
			$tree = $this->resolve_filename($tree,dirname($params['path']));
		}
		

		$stat = `GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git blame -p master -- {$params['path']}`;
		$blame = Git\Util\Blame\Parser::parse($stat);
		
		$this->render("blame.htm",array(
					'user'         => $user,
					'owner'        => $owner,
					'repository'   => $owner->getRepository($params['repository']),
					'commit'       => $commit,
					'tree'         => $tree,
					'blame'         => $blame,
					'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
					'current_path' => dirname($params['path']) . '/',
					'path'         => $params['path']
		));
	}
	
	public function onAbout()
	{
		$user = $this->getUser();
		$this->render('about.htm',array(
			'user'=>$user
		));
	}
	
	public function onCommitsHisotry($params)
	{
		$path = $params['path'];
		$owner = User::get(UserPointer::getIdByNickname($params['user']),"user");

		$user = $this->getUser();
		$repository = $owner->getRepository($params['repository']);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$ref = $repo->lookupRef("refs/heads/master");
		$commit = $repo->getCommit($ref->getId());
		$walker = $repo->getWalker();
		$walker->push($commit->getId());
		$i=0;
		$commits = array();
		$last = null;
		while($i < 20 && $tmp = $walker->next()) {
			$tree = $tmp->getTree();
			$t = $this->resolve_filename($tree, $path);
			if($t instanceof Git\Object && $last != $t->getId()) {
				$commits[] = $tmp;
				$last = $t->getId();
				$i++;
			}
		}
		$this->render("commits.htm",array(
					'user'=> $user,
					'owner' => $owner,
					'repository'=> $owner->getRepository($params['repository']),
					"commits" => $commits
		));
	}
}
