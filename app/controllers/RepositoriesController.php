<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class PusedoCommit
{
	protected $message;
	protected $author;
	protected $committer;
	
	public function getAuthor()
	{
		return $this->author;
	}
	
	public function getCommitter()
	{
		return $this->committer;
	}
	
	public function getMessage()
	{
		return $this->message;
	}

	public function __construct($commit)
	{
		if($commit instanceof \Git\Commit){
			$this->message = $commit->getMessage();
			$this->author = $commit->getAuthor();
			$this->committer = $commit->getCommitter();
		}
	}
}

class RepositoriesController extends GitHQ\Bundle\AbstractController
{
	public $view = "RepositoriesView";
	
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
					$repo->watch($user,$user);
					
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
			$ref = $repo->lookupRef("refs/heads/{$repository->getDefaultBranch()}");
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

		$latest = array();
		
		
		if ($tree){
			/**
			 * obtaining each latest commits. it's very hard for me to looking correct history.
			 * so i choose easy solution at this time.
			 **/
			$redis = GitHQ\Bundle\AbstractController::getRedisClient();
			$cache = $redis->get("ccache.{$owner->getKey()}.{$repository->getId()}.{$tree->getId()}");
			if (!$cache) {
				foreach($tree->getIterator() as $entry) {
					$commit_id = trim(`GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git log --format=%H -n1 -- {$entry->name}`);
					$latest[$entry->name] = new PusedoCommit($repo->getCommit($commit_id));
				}
				$redis->set("ccache.{$owner->getKey()}.{$repository->getId()}.{$tree->getId()}",serialize($latest));
				$redis->expire("ccache.{$owner->getKey()}.{$repository->getId()}.{$tree->getId()}",86400);
			} else {
				$latest = unserialize($cache);
			}
		}

		return $this->getDefaultView()
					->setTemplate("repository.htm")
					->prepareResponse(array(
						'user'        => $user,
						'owner'       => $owner,
						'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(),$repository->getId()),	
						'repository'  => $repository,
						'commit'      => $commit,
						'tree'        => $tree,
						'data'        => $data,
						'watcher'     => Repository::getWatchedCount($owner, $repository),
						'latests'     => $latest,
						'tab'         => 'file',
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
		if ($user) {
			$repository->watch($owner,$user);
		}
		
		return new RedirectResponse($this->generateUrl('repositories.top',array(
			"user"       => $owner->getNickname(),
			"repository" => $repository->getName(),
		)));
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
		$ref = $repo->lookupRef("refs/heads/{$repository->getDefaultBranch()}");
		$commit = $repo->getCommit($commit);
		
		$message = $commit->getMessage();
		$lines = explode("\n",$message);
		$first = array_shift($lines);
		$message = join("\n",$lines);
	
		return $this->getDefaultView()
					->setTemplate("commit.htm")
					->prepareResponse(array(
						'owner'      => $owner,
						'repository' => $repository,
						"commit"     => $commit,
						"diff"       => $struct,
						"first"      => $first,
						"message"    => $message,
						'watcher'    => Repository::getWatchedCount($owner, $repository),
		));
	}
	
	public function onCommits($user, $repository, $refs)
	{
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
	
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		try {
			$ref = $repo->lookupRef("refs/heads/{$refs}");
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
		
		return $this->getDefaultView()
					->setTemplate("commits.htm")
					->prepareResponse(array(
						'owner'       => $owner,
						'repository'  => $repository,
						"commits"     => $commits,
						'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
						'watcher'     => Repository::getWatchedCount($owner, $repository),
						'tab'         => 'commit',
		
		));
	}
	
	/**
	 * create a repository 
	 * 
	 * @return RedirectResponse $response
	 */
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
				
				$repo->watch($user,$user);
				$_SESSION['user'] = $user;
			}
			$user->save();
			
		}

		return new RedirectResponse($this->generateUrl('repositories.top',array(
					"user"       => $user->getNickname(),
					"repository" => $repo->getName(),
		)));
	}

	public function onNew()
	{
		return $this->getDefaultView()
					->setTemplate("new.htm")
					->prepareResponse();
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
		$ext = pathinfo($path,\PATHINFO_EXTENSION);

		switch($ext) {
			case "md":
				header("Content-type: text/plain");
				break;
		}
		
		$response = new UIKit\Framework\HTTPFoundation\Response\HTTPResponse($blob->data);
		return $response;
	}

	public function onBlob($user, $repository, $refs, $path)
	{
	
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		if (!$repository) {
			header("HTTP1.0 404 Not found");
			exit;
		}
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$refm = new \Git\Reference\Manager($repo);
		$branches = $refm->getList();
		$data = null;		
		
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
				if ($ext == "htm"){
					$ext = "html+jinja";
				}
				$data = Albino::colorize($blob->data,$ext);
				if ($data){
					$data = preg_replace("|</div>|","",preg_replace("|<div class=\"highlight\">|","",preg_replace("|</?pre>|m","",$data)));
					$lines = explode("\n",$data);
					foreach ($lines as $o => $line) {
						$lines[$o] = "<div class=\"line\">" . $line . "</div>";
					}
					$data = "<div class=\"highlight\"><pre>" . join("",$lines) . "</pre></div>";
				}
				break;
		}
		
		if (!$data) {
			$data = htmlspecialchars($blob->data);
			$lines = explode("\n",$data);
			foreach ($lines as $o => $line) {
				$lines[$o] = "<div class=\"line\">" . $line . "</div>";
			}
			$data = "<pre>" . join("",$lines) . "</pre>";
		}
	
		$keys = explode("/",$path);
		$path_parts = array();
		$stack = array();
		foreach($keys as $key) {
			$stack[] = $key;
			$p = join("/",$stack);
			$path_parts[$key] = $p;
		}
	
		$file = (isset($_REQUEST['_pjax'])) ? "_blob.htm" : "repository.htm";		 
		$this->render($file,array(
			'owner'        => $owner,
			'repository'   => $repository,
			'commit'       => $commit,
			'blob'         => $blob,
			'data'         => $data,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'current_path' => dirname($path) . '/',
			'path'         => $path,
			'path_parts'   => $path_parts,
			'refs'         => $refs,
			'img'          => $img,
			'lines'        => $lines,
			'tab'          => 'file',
		));
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
		
		$latest = array();
		
		/**
		 * obtaining each latest commits. it's very hard for me to looking correct history.
		 * so i choose easy solution at this time.
		 **/
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$cache = $redis->get("ccache.{$owner->getKey()}.{$repository->getId()}.{$tree->getId()}");
		if (!$cache) {
			foreach($tree->getIterator() as $entry) {
				$p = ltrim("{$path}/{$entry->name}","/");
				$commit_id = trim(`GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git log --format=%H -n1 -- {$p}`);
				$latest[$entry->name] = new PusedoCommit($repo->getCommit($commit_id));
			}
			$redis->set("ccache.{$owner->getKey()}.{$repository->getId()}.{$tree->getId()}",serialize($latest));
			$redis->expire("ccache.{$owner->getKey()}.{$repository->getId()}.{$tree->getId()}",86400);
		} else {
			$latest = unserialize($cache);
		}
		
		$parent_dir = dirname($current_path);
		$file = (isset($_REQUEST['_pjax'])) ? "_tree.htm" : "repository.htm";
		
		$this->render($file,array(
			'owner'        => $owner,
			'repository'   => $repository,
			'commit'       => $commit,
			'tree'         => $tree,
			'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'current_path' => $current_path,
			'parent_dir'   => $parent_dir,
			'watcher'      => Repository::getWatchedCount($owner, $repository),
			'latests'      => $latest,
			'tab'          => 'file',
		));
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

		return $this->getDefaultView()
					->setTemplate("blame.htm")
					->prepareResponse(array(
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
 
		return $this->getDefaultView()
					->setTemplate("commits.htm")
					->prepareResponse(array(
						'owner'      => $owner,
						'repository' => $repository,
						"commits"    => $commits,
						'tab'        => 'file',	
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
		$atags = array();
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


		return $this->getDefaultView()
					->setTemplate("tags.htm")
					->prepareResponse(array(
						'owner'        => $owner,
						'repository'   => $repository,
						'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
						'tags'         => $tags,
						'watcher'      => Repository::getWatchedCount($owner, $repository),
						'tab'          => 'tag',
		));
	}

	public function onBranches($user, $repository)
	{
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
	
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$tags = array();
		$atags = array();
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

		return $this->getDefaultView()
					->setTemplate("branches.htm")
					->prepareResponse(array(
						'owner'        => $owner,
						'repository'   => $repository,
						'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
						'branches'     => $branches,
						'watcher'      => Repository::getWatchedCount($owner, $repository),
						'tab'          => 'branch',
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
	
	/**
	 * paste from php.net
	 * 
	 * @param string $gzData
	 */
	protected function gzBody($gzData){ 
	    if(substr($gzData,0,3)=="\x1f\x8b\x08"){ 
	        $i=10; 
	        $flg=ord(substr($gzData,3,1)); 
	        if($flg>0){ 
	            if($flg&4){ 
	                list($xlen)=unpack('v',substr($gzData,$i,2)); 
	                $i=$i+2+$xlen; 
	            } 
	            if($flg&8) $i=strpos($gzData,"\0",$i)+1; 
	            if($flg&16) $i=strpos($gzData,"\0",$i)+1; 
	            if($flg&2) $i=$i+2; 
	        } 
	        return gzinflate(substr($gzData,$i,-8)); 
	    } 
	    else return $gzData; 
	}
	
	public function onTransport($user,$repository,$path)
	{
		$owner = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		
		$repo = new \Git\Repository("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		$tags = array();
		$atags = array();
		$branches = array();
		$request = $this->get('request');
		
		switch($path) {
			case "HEAD":
				if (is_file("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/HEAD")) {
					echo file_get_contents("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/HEAD");
				}
				break;
			case "git-receive-pack":
				header("HTTP/1.0 405 Method Not Allowed");
				exit;
				
				$input = file_get_contents("php://input");
				header("Content-type: application/x-git-receive-pack-result");
				$input = $this->gzBody($input);
				
				$descriptorspec = array(
				0 => array("pipe", "r"),
				1 => array("pipe", "w"),
				);
					
				ob_end_flush();
				$p = proc_open("git-receive-pack --stateless-rpc /home/git/repositories/{$owner->getKey()}/{$repository->getId()}",$descriptorspec,$pipes);
				if (is_resource($p)){
					fwrite($pipes[0],$input);
					fclose($pipes[0]);
					while (!feof($pipes[1])) {
						$data = fread($pipes[1],8192);
						echo $data;
					}
					fclose($pipes[1]);
					proc_close($p);
				}
				exit;
				break;
			case "git-upload-pack":
				$input = file_get_contents("php://input");
				header("Content-type: application/x-git-upload-pack-result");
				$input = $this->gzBody($input);
				
				$descriptorspec = array(
					0 => array("pipe", "r"),
					1 => array("pipe", "w"),
				);
					
				ob_end_flush();
				$p = proc_open("git-upload-pack --stateless-rpc /home/git/repositories/{$owner->getKey()}/{$repository->getId()}",$descriptorspec,$pipes);
				if (is_resource($p)){
					fwrite($pipes[0],$input);
					fclose($pipes[0]);
					while (!feof($pipes[1])) {
						$data = fread($pipes[1],8192);
						echo $data;
					}
					fclose($pipes[1]);
					proc_close($p);				
				}
				exit;
				break;
			case "info/refs":
				
				if ($request->get("service") == "git-upload-pack") {
					$input = file_get_contents("php://input");
					header("Content-type: application/x-git-upload-pack-advertisement");

					$descriptorspec = array(
						0 => array("pipe", "r"),
						1 => array("pipe", "w"),
					);
					
					$p = proc_open("git-upload-pack --stateless-rpc --advertise-refs /home/git/repositories/{$owner->getKey()}/{$repository->getId()}",$descriptorspec,$pipes);
					if (is_resource($p)){
						fwrite($pipes[0],$input);
						fclose($pipes[0]);
						$data = stream_get_contents($pipes[1]);
						fclose($pipes[1]);
						proc_close($p);
						
						$str = "# service=git-upload-pack\n";
						$data = str_pad(base_convert(strlen($str)+4, 10, 16),4,'0',STR_PAD_LEFT) . $str . '0000' . $data;
						header("Content-length: " . strlen($data));
						echo $data;
						exit;
					}
				} else if ($request->get("service") == "git-receive-pack") {
					$input = file_get_contents("php://input");
					header("Content-type: application/x-git-receive-pack-advertisement");

					$descriptorspec = array(
						0 => array("pipe", "r"),
						1 => array("pipe", "w"),
					);
					
					$p = proc_open("git-receive-pack --stateless-rpc --advertise-refs /home/git/repositories/{$owner->getKey()}/{$repository->getId()}",$descriptorspec,$pipes);
					if (is_resource($p)){
						fwrite($pipes[0],$input);
						fclose($pipes[0]);
						$data = stream_get_contents($pipes[1]);
						fclose($pipes[1]);
						proc_close($p);
						
						$str = "# service=git-receive-pack\n";
						$data = str_pad(base_convert(strlen($str)+4, 10, 16),4,'0',STR_PAD_LEFT) . $str . '0000' . $data;
						header("Content-length: " . strlen($data));
						echo $data;
						exit;
					}
				} else {
					foreach($repo->getReferences() as $ref) {
						printf("%s\t%s\n",$ref->oid,$ref->name);
					}
				}
				break;
			case "objects/info/packs":
				/* @todo : looking specification. this implementation too about */
				if (is_dir("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/objects/pack")) {
					$dir = opendir("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/objects/pack");
					if ($dir) {
						while (($file = readdir($dir)) !== false) {
							$ext = pathinfo($file,\PATHINFO_EXTENSION);
							if ($ext == "pack") {
								printf("P %s\n",$file);
							}
						}
					}
				} else {
					header("HTTP/1.0 404 Not Found");
				}
				
				break;
			case "objects/info/alternates":
				header("HTTP/1.0 404 Not Found");
				break;
			case "objects/info/http-alternates":
				header("HTTP/1.0 404 Not Found");
				break;
			default:
				if (preg_match("!objects/pack/(?P<path>.+)!",$path,$match)) {
					if (is_file("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/objects/pack/{$match['path']}")) {
							$ext = pathinfo($match['path'],\PATHINFO_EXTENSION);
							$data = file_get_contents("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/objects/pack/{$match['path']}");
							if ($ext == "idx") {
								header("Content-type: application/x-git-packed-objects-toc");
								header("Content-length: " . strlen($data));
								echo $data;
							} else if ($ext == "pack") {
								header("Content-type: application/x-git-packed-objects");
								header("Content-length: " . strlen($data));
								echo $data;
							}
					} else {
						header("HTTP/1.0 404 Not Found");
					}				
				} else if (preg_match("!objects/(?P<path>.+)!",$path,$match)) {
					if (is_file("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/objects/{$match['path']}")) {
						$data = file_get_contents("/home/git/repositories/{$owner->getKey()}/{$repository->getId()}/objects/{$match['path']}");
						header("Content-type: application/x-git-loose-object");
						header("Content-length: " . strlen($data));
						echo $data;
					} else {
						error_log("path {$match['path']} not found");
						header("HTTP/1.0 404 Not Found");
					}
				} else {
					header("HTTP/1.0 404 Not Found");
				}
		}
	}
}
