<?php
class RootController extends GitHQ\Bundle\AbstractController
{	
	public function onDefault($params =  array())
	{
		$this->get("logger")->addDebug("Hey");
		
		$data = null;
		$user = $this->getUser();
		$organizations = null;
		
		if ($this->getRequest()->isPost()) {
			$owner = User::get(User::getIdByNickname($_REQUEST['user']),"user");
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
			$owner = User::get(User::getIdByNickname($params['controller.orig']),"user");
			$repository = $owner->getRepository($params['action.orig']);
						
			if (!$repository) {
				return $this->render("404.htm",array());
			}
			if (!$repository->hasPermission($owner, $user)) {
				return $this->render("403.htm",array());
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
				'watcher'      => Repository::getWatchedCount($owner, $repository),
			));
		} else {
			$timeline = Activity::getGlobalTimeline();
			if ($user) {
				$organizations = $user->getJoinedOrganizations();
			}
			
			$this->render("index.htm",array(
				'user'=>$user,
				'timeline' => $timeline,
				'organizations' => $organizations,
			));
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
		
		$owner = User::get(User::getIdByNickname($params['user']),'user');
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
		
		$img = false;
		$ext = pathinfo($params['path'],\PATHINFO_EXTENSION);
		switch ($ext) {
			case 'jpg':
			case 'gif':
			case 'png':
				$img = true;
				break;
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
		
		$keys = explode("/",$params['path']);
		$path_parts = array();
		$stack = array();
		foreach($keys as $key) {
			$stack[] = $key;
			$path_parts[$key] = join("/",$stack);
		}

		if (isset($_REQUEST['_pjax'])) {
			$this->render("_blob.htm",array(
						'user'         => $user,
						'owner'        => $owner,
						'repository'   => $repository,
						'commit'       => $commit,
			//			'tree'         => $tree,
						'blob'         => $blob,
						'data'         => $data,
						'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
						'current_path' => dirname($params['path']) . '/',
						'path'         => $params['path'],
						'path_parts'   => $path_parts,
						'refs'         => $params['refs'],
						'img'          => $img,
			));
				
		} else {
		$this->render("repository.htm",array(
			'user'         => $user,
			'owner'        => $owner,
			'repository'   => $repository,
			'commit'       => $commit,
//			'tree'         => $tree,
			'blob'         => $blob,
			'data'         => $data,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
			'current_path' => dirname($params['path']) . '/',
			'path'         => $params['path'],
			'path_parts'   => $path_parts,
			'refs'         => $params['refs'],
			'img'          => $img,
		));
		}
	}

	public function onTree($params)
	{
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
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
		} else {
			$tree = $commit->getTree();
		}
		$parent_dir = dirname($current_path);
		
		if (isset($_REQUEST['_pjax'])) {
			$this->render("_tree.htm",array(
							'user'         => $user,
							'owner'        => $owner,
							'repository'   => $repository,
							'commit'       => $commit,
							'tree'         => $tree,
							'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
							'current_path' => $current_path,
							'parent_dir'   => $parent_dir,
							'watcher'      => Repository::getWatchedCount($owner, $repository),
			));
		} else {
			$this->render("repository.htm",array(
				'user'         => $user,
				'owner'        => $owner,
				'repository'   => $repository,
				'commit'       => $commit,
				'tree'         => $tree,
				'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
				'current_path' => $current_path,
				'parent_dir'   => $parent_dir,
				'watcher'      => Repository::getWatchedCount($owner, $repository),
			));
		}
	}


	public function onSession()
	{
		if($this->getRequest()->isPost()) {
			$user = User::get(User::getIdByNickname($_REQUEST['username']),"user");
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
		$owner = User::get(User::getIdByNickname($params['user']),"user");
		$user = $this->getUser();
		$repository = $owner->getRepository($params['repository']);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$commit  = escapeshellarg($params['commit']);
		$stat = `GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git log -p {$commit} -n1`;
		$struct = Git\Util\Diff\Parser::parse($stat);
		
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
		$owner = User::get(User::getIdByNickname($params['user']),"user");
		$user = $this->getUser();
		$repository = $owner->getRepository($params['repository']);
		
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		try {
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
		} catch (\InvalidArgumentException $e) {
			$commits = array();
		}
		$this->render("commits.htm",array(
			'user'=> $user,
			'owner' => $owner,
			'repository'=> $repository,
			"commits" => $commits,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
		
		));
	}
	
	public function onConnect()
	{
		$user_id = $this->get('facebook')->getUser();
		if ($user_id) {
			if ($user = User::get($user_id,'user')) {
				$_SESSION['user'] = $user;
				header("Location: " . $this->get('application.url'));
			} else {
				header("Location: {$this->get('application.url')}/signup/free");
			}
		} else {
			header("Location: " . $this->get('facebook')->getLoginUrl());
		}
	}
	
	public function onUser($params)
	{
		$owner = User::get(User::getIdByNickname($params['user']),"user");
		$user = $this->getUser();
		
		$timeline = Activity::getTimelineByUserId($owner->getKey());
		
		$this->render("user.htm",array(
			'owner' => $owner,
			'user' => $user,
			'timeline' => $timeline,
		));
	}
	
	public function onBlame($params)
	{
		$user = $this->getUser();
		
		$owner = User::get(User::getIdByNickname($params['user']),'user');
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
		
		$p  = escapeshellarg($params['path']);
		$stat = `GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git blame -p master -- {$p}`;
		$blame = Git\Util\Blame\Parser::parse($stat);
		
		$this->render("blame.htm",array(
					'user'         => $user,
					'owner'        => $owner,
					'repository'   => $owner->getRepository($params['repository']),
					'commit'       => $commit,
					'tree'         => $tree,
					'blame'        => $blame,
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
		$owner = User::get(User::getIdByNickname($params['user']),"user");

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
	
	public function onTags($params)
	{
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$tags = array();
		$branches = array();
		foreach($repo->getReferences() as $ref) {
			if(preg_match("/refs\/tags/",$ref->name)) {
				$ref->name = basename($ref->name);
				$ctags[$ref->name] = $ref;
				$atags[] = $ref->name;
			} else if (preg_match("/refs\/heads/",$ref->name)) {
				$branches[] = $ref;
			}
		}
		if($atags){
		foreach(\chobie\VersionSorter::rsort($atags) as $id){
			$tags[] = $ctags[$id];
		}
		}
				
		$this->render("tags.htm",array(
					'user'         => $user,
					'owner'        => $owner,
					'repository'   => $repository,
					'commit'       => $commit,
					'tree'         => $tree,
					'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
					'tags' => $tags,
		));
		
	}

	public function onZipBall($params)
	{
		ini_set("max_memory","128M");
		
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
	
		$spec = array(
			0 => array("pipe","r"),
			1 => array("pipe","w")
		);
		$proc = proc_open("git archive --format zip {$params['tag']}",$spec,$pipes,"/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		if(is_resource($proc)) {
			fclose($pipes[0]);
			$content = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);
		}
		header("Content-Disposition: inline; filename=\"{$owner->getNickname()}-{$repository->getName()}-{$params['tag']}.zip\"");
		header("Content-type: application/zip");
		header("Content-Length: " . strlen($content));
		echo $content;
		exit;
		
	}

	public function onRaw($params)
	{
		$user = $this->getUser();
	
		$owner = User::get(User::getIdByNickname($params['user']),'user');
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
	
		echo $blob->data;
	}
}
