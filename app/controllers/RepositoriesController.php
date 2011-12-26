<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class RepositoriesController extends GitHQ\Bundle\AbstractController
{
	public function onTop($user, $repository)
	{
		$owner = User::getByNickname($user);
		if (!$owner) {
			return false;
		}
		
		$user = $this->getUser();
		$repository = $owner->getRepository($repository);
		
		if ($this->getRequest()->isPost()) {
			/* fork */
			$owner = User::getByNickname($this->get('request')->get('user'));
			if (!$user->getRepository($_REQUEST['repository'])) {
				$origin = $owner->getRepository($_REQUEST['repository']);
		
				$user = User::fetchLocked($user->getKey());
				if ($repo = $origin->fork($owner, $user)) {
					$user->addRepository($repo);
					$user->save();
		
					$_SESSION['user'] = $user;
				}
				return $this->render("fork.htm");
			} else {
				throw new \Exception("could not fork repository.");
			}
		}
		
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
		
		} catch (\InvalidArgumentException $e) {
			$commit = null;
			$tree = null;
		}
		
		$this->render("repository.htm",array(
						'user'        => $user,
						'owner'       => $owner,
						'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(),$repository->getId()),	
						'repository'  => $repository,
						'commit'      => $commit,
						'tree'        => $tree,
						'data'        => $data,
						'watcher'     => Repository::getWatchedCount($owner, $repository),
		));
	}
	
	/**
	 * increment watcher
	 * 
	 * @todo considering ajax request.
	 *  
	 * @param string $user nickname
	 * @param string $repository repository name
	 * @return RedirectResponse
	 */
	public function onWatch($user, $repository)
	{
		$owner      = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$user = $this->getUser();
		$repository->watch($owner,$user);
		
		return new RedirectResponse($this->get('application.url') . "/{$owner->getNickname()}/{$repository->getName()}");
	}

	public function onCommit($user, $repository, $commit)
	{
		$owner = User::getByNickname($user);
		if (!$owner) {
			return $this->on404();
		}
	
		$repository = $owner->getRepository($repository);
	
		if (!$repository) {
			return $this->on404();
		}
	
		$struct = Git_Util::CommitLog($owner,$repository,$commit);
		
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$ref = $repo->lookupRef("refs/heads/master");
		$commit = $repo->getCommit($commit);
	
		$this->render("commit.htm",array(
						'owner'      => $owner,
						'repository' => $repository,
						"commit"     => $commit,
						"diff"       => $struct,
		));
	}
	
	public function onCommits($user, $repository)
	{
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
	
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
				'owner'       => $owner,
				'repository'  => $repository,
				"commits"     => $commits,
				'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
	
		));
	}
	
	public function onDefault()
	{
		$request = $this->get('request');
		
		if($request->isPost()){ 
			$project_name = $request->get('project_name');
			$description  = $request->get('description');
			$homepage_url = $request->get('homepage_url');
			
			$user = User::fetchLocked($_SESSION['user']->getKey());

			$repo = new Repository($project_name);
			$id = $user->getNextRepositoryId();
			$repo->setId($id);
			$repo->setDescription($description);
			$repo->setDescription($homepage_url);
			
			if ($repo->create($user->getKey())) {
				$user->addRepository($repo);
				if ($request->get('visibility') == 1) {
					$repo->setPrivate();
				} else {
					$this->get('event')->emit(new UIKit\Framework\Event('repository.new',array($user,$repo)));
				}
				$_SESSION['user'] = $user;
				$repo->watch($user,$user);
			}
			$user->save();
			
			return new RedirectResponse($this->get('application.url'));
		}
	}

	public function onNew()
	{
		$this->render("new.htm",array());
	}

	public function onRaw($user, $repository, $refs, $path)
	{	
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$refm = new \Git\Reference\Manager($repo);
		$branches = $refm->getList();
		
		$ref = $repo->lookupRef("refs/heads/{$refs}");
		$commit = $repo->getCommit($ref->getId());
		$current_path = '';
	
		if ($path) {
			$paths = explode('/',$path);
			if(count($paths)> 1) {
				$current_path = join('/',$paths) . '/';
			} else{
				$current_path = $path . '/';
			}
			$tree = $commit->getTree();
			$blob = $this->resolve_filename($tree,$path);
			$tree = $this->resolve_filename($tree,dirname($path));
		}
		
		header("Content-type: text/plain");
		echo $blob->data;
	}

	public function onBlob($user, $repository, $refs, $path)
	{
	
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$refm = new \Git\Reference\Manager($repo);
		$branches = $refm->getList();		
		
		$ref = $repo->lookupRef("refs/heads/{$refs}");
		$commit = $repo->getCommit($ref->getId());
		$current_path = '';
		if ($path) {
			$paths = explode('/',$path);
			if(count($paths)> 1) {
				$current_path = join('/',$paths) . '/';
			} else{
				$current_path = $path . '/';
			}
			$tree = $commit->getTree();
			$blob = $this->resolve_filename($tree,$path);
			$tree = $this->resolve_filename($tree,dirname($path));
		}
	
		$img = false;
		$ext = pathinfo($path,\PATHINFO_EXTENSION);
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
	
		$keys = explode("/",$path);
		$path_parts = array();
		$stack = array();
		foreach($keys as $key) {
			$stack[] = $key;
			$path_parts[$key] = join("/",$stack);
		}
	
		if (isset($_REQUEST['_pjax'])) {
			$this->render("_blob.htm",array(
							'owner'        => $owner,
							'repository'   => $repository,
							'commit'       => $commit,
			//			'tree'         => $tree,
							'blob'         => $blob,
							'data'         => $data,
							'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
							'current_path' => dirname($path) . '/',
							'path'         => $path,
							'path_parts'   => $path_parts,
							'refs'         => $path,
							'img'          => $img,
			));
	
		} else {
			$this->render("repository.htm",array(
				'owner'        => $owner,
				'repository'   => $repository,
				'commit'       => $commit,
			//			'tree'         => $tree,
				'blob'         => $blob,
				'data'         => $data,
				'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
				'current_path' => dirname($path) . '/',
				'path'         => $path,
				'path_parts'   => $path_parts,
				'refs'         => $refs,
				'img'          => $img,
			));
		}
	}

	public function onTree($user, $repository, $refs, $path)
	{
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		if (!$repository) {
			return false;
		}
		
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
	
		$ref = $repo->lookupRef("refs/heads/{$refs}");
		$commit = $repo->getCommit($ref->getId());
		$current_path = '';
		if ($path) {
			$paths = explode('/',$path);
			if(count($paths)> 1) {
				$current_path = join('/',$paths) . '/';
			} else{
				$current_path = $path . '/';
			}
			$tree = $commit->getTree();
			$tree = $this->resolve_filename($tree,$path);
		} else {
			$tree = $commit->getTree();
		}
		$parent_dir = dirname($current_path);
	
		if (isset($_REQUEST['_pjax'])) {
			$this->render("_tree.htm",array(
								'owner'        => $owner,
								'repository'   => $repository,
								'commit'       => $commit,
								'tree'         => $tree,
								'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
								'current_path' => $current_path,
								'parent_dir'   => $parent_dir,
								'watcher'      => Repository::getWatchedCount($owner, $repository),
			));
		} else {
			$this->render("repository.htm",array(
					'owner'        => $owner,
					'repository'   => $repository,
					'commit'       => $commit,
					'tree'         => $tree,
					'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
					'current_path' => $current_path,
					'parent_dir'   => $parent_dir,
					'watcher'      => Repository::getWatchedCount($owner, $repository),
			));
		}
	}

	public function onBlame($user, $repository, $refs, $path)
	{
	
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$refm = new \Git\Reference\Manager($repo);
		$branches = $refm->getList();
		
		$ref = $repo->lookupRef("refs/heads/{$refs}");
		$commit = $repo->getCommit($ref->getId());
		$current_path = '';
		if ($path) {
			$paths = explode('/',$path);
			if(count($paths)> 1) {
				$current_path = join('/',$paths) . '/';
			} else{
				$current_path = $path . '/';
			}
			$tree = $commit->getTree();
			$tree = $this->resolve_filename($tree,dirname($path));
		}
	
		$blame = Git_Util::Blame($owner,$repository, $path);
	
		$this->render("blame.htm",array(
						'owner'        => $owner,
						'repository'   => $repository,
						'commit'       => $commit,
						'tree'         => $tree,
						'blame'        => $blame,
						'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
						'current_path' => dirname($path) . '/',
						'path'         => $path
		));
	}

	public function onCommitsHisotry($user, $repository, $refs, $path)
	{
		$owner = User::getByNickname($user);		
		$repository = $owner->getRepository($repository);
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$ref = $repo->lookupRef("refs/heads/{$refs}");
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
			'owner'      => $owner,
			'repository' => $repository,
			"commits"    => $commits
		));
	}
	
	protected function getTags($repo)
	{
		
	}

	public function onTags($user, $repository)
	{
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		
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
			'owner'        => $owner,
			'repository'   => $repository,
			'commit'       => $commit,
			'tree'         => $tree,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'tags'         => $tags,
		));

	}

	public function onZipBall($user, $repository, $zipball, $tag)
	{
		ini_set("max_memory","128M");
		
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$user = $this->getUser();
		
		$content = Git_Util::Archive($owner, $repository, $tag);
		
		header("Content-Disposition: inline; filename=\"{$owner->getNickname()}-{$repository->getName()}-{$tag}.zip\"");
		header("Content-type: application/zip");
		header("Content-Length: " . strlen($content));
		echo $content;
		exit;
	}
}
