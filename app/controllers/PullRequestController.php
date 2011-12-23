<?php
class PullRequestController extends GitHQ\Bundle\AbstractController
{
	
	public function onFiles($params)
	{
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$params['id'])),'issue');
		$ref = $issue->getRef();
		
		$requestor = User::get($ref['owner'],'user');
		$req_repo = $requestor->getRepositoryById($ref['repository']);
		
		$commit_id = trim(`git --git-dir=/home/git/repositories/{$ref['owner']}/{$ref['repository']} rev-parse --verify master`);
		$spec = array(
					'0' => array("pipe","r"),
					'1' => array("pipe","w"),
		);
		$p = proc_open("git diff master..{$commit_id}",$spec,$pipes,"/home/git/repositories/{$owner->getKey()}/{$repository->getId()}",array("GIT_ALTERNATE_OBJECT_DIRECTORIES"=>"/home/git/repositories/{$ref['owner']}/{$ref['repository']}/objects"));
		if (is_resource($p)) {
			fclose($pipes[0]);
			$data = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($p);
		}
		
		$struct = \Git\Util\Diff\Parser::parse($data);
						
		$this->render("files.htm",array(
			'user' => $user,
			'owner' => $owner,
			'repository' => $repository,
			'issue' => $issue,
			'diff' => $struct,
		));
	}
	
	public function onClose($params)
	{
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$params['id'])),'issue');
		$ref = $issue->getRef();
		
		$requestor = User::get($ref['owner'],'user');
		$req_repo = $requestor->getRepositoryById($ref['repository']);
		
		$commit_id = trim(`git --git-dir=/home/git/repositories/{$ref['owner']}/{$ref['repository']} rev-parse --verify master`);
		
		$spec = array(
					'0' => array("pipe","r"),
					'1' => array("pipe","w"),
		);
		$p = proc_open("git rev-list {$commit_id} .. master",$spec,$pipes,"/home/git/repositories/{$owner->getKey()}/{$repository->getId()}",array("GIT_ALTERNATE_OBJECT_DIRECTORIES"=>"/home/git/repositories/{$ref['owner']}/{$ref['repository']}/objects"));
		if (is_resource($p)) {
			fclose($pipes[0]);
			$data = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($p);
		}
		$lines = explode("\n",$data);
		$commits = array();
		foreach($lines as $line) {
			if(empty($line)) {
				continue;
			}
			$commits[] = $line;
		}
		
		$res = array();
		foreach($commits as $commit_id) {
			$repo = new \Git\Repository("/home/git/repositories/{$ref['owner']}/{$ref['repository']}");
			$res[] = $repo->getCommit($commit_id);
		}
		
		/* merge: actually this implementation looks bad. but it's difficult to apply each commit by lib. for now, we choose this */
		system("mkdir -p /home/git/workdir/{$owner->getNickname()}/{$repository->getId()}");
		system("chmod 777 -R /home/git/workdir/{$owner->getNickname()}/{$repository->getId()}");
		system("git clone -l /home/git/repositories/{$owner->getKey()}/{$repository->getId()} /home/git/workdir/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()}");
		chdir("/home/git/workdir/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()}/");
		system("git pull /home/git/repositories/{$ref['owner']}/{$ref['repository']}",$ret);
		if ($ret === 0) {
			system("git push origin master",$ret);
			chdir("/");
			system("rm -rf /home/git/workdir/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()}");
				
			$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$params['id'])),'issue');
			$issue->setStatus(Issue::CLOSED);
			$issue->save();
			header("Location: /");
		} else {
			echo "Something went to wrong. please merge manually.";
		}
		
	}
	
	public function onPullRequest($params)
	{
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$params['id'])),'issue');
		$ref = $issue->getRef();

		$requestor = User::get($ref['owner'],'user');
		$req_repo = $requestor->getRepositoryById($ref['repository']);
		
		$commit_id = trim(`git --git-dir=/home/git/repositories/{$ref['owner']}/{$ref['repository']} rev-parse --verify master`);
		
		$spec = array(
			'0' => array("pipe","r"),
			'1' => array("pipe","w"),
		);
		$p = proc_open("git rev-list {$commit_id} .. master",$spec,$pipes,"/home/git/repositories/{$owner->getKey()}/{$repository->getId()}",array("GIT_ALTERNATE_OBJECT_DIRECTORIES"=>"/home/git/repositories/{$ref['owner']}/{$ref['repository']}/objects"));
		if (is_resource($p)) {
			fclose($pipes[0]);
			$data = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($p);
		}
		$lines = explode("\n",$data);
		$commits = array();
		foreach($lines as $line) {
			if(empty($line)) {
				continue;
			}
			$commits[] = $line;
		}

		$res = array();
		foreach($commits as $commit_id) {
			$repo = new \Git\Repository("/home/git/repositories/{$ref['owner']}/{$ref['repository']}");
			$res[] = $repo->getCommit($commit_id);
		}

		/* merge: actually this implementation looks bad. but it's difficult to apply each commit by lib. for now, we choose this */
		system("mkdir -p /tmp/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()}");
		system("git clone -l /home/git/repositories/{$owner->getKey()}/{$repository->getId()} /tmp/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()} 2>");
		system("GIT_DIR=/tmp/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()} git pull /home/git/repositories/{$ref['owner']}/{$ref['repository']} 2>",$ret);
		system("rm -rf /tmp/{$owner->getNickname()}/{$repository->getId()}/{$issue->getId()}");
		
		$this->render("pullrequest.htm",array(
					"user" => $user,
					"issue" => $issue,
					"owner" => $owner,
					"repository" => $repository,
					'requestor' => $requestor,
					'req_repo' => $req_repo,
					'commits' => $res,
					'ret' => $ret,
					'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($params['repository'])->getId()),
		
		));
	}

	public function onNew($params)
	{
		$user = $this->getUser();
		$owner = User::get(User::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		
		
		$origin = User::get($repository->getOrigin(),'user');
		$origin_repo = $origin->getRepository($params['repository']);
		
		if($this->getRequest()->isPost()) {
			$id = IssueReferences::getNextId($origin->getKey(),$origin_repo->getId());
			$issue = new Issue(join(':',array($origin->getKey(),$origin_repo->getId(),$id)));
			$issue->setId($id);
			$issue->setOwner($origin->getKey());
			$issue->setRepositoryId($origin_repo->getId());
			$issue->setAuthor($user->getKey());
			$issue->setTitle($_REQUEST['title']);
			$issue->setBody($_REQUEST['contents']);
			$issue->setPullrequest();
			$issue->attachRef($user->getKey(), $_REQUEST['ref'],$repository->getId());
			
			if ($issue->create()) {
				$a = new Activity(Activity::getNextId(),'activity');
				$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
				$a->setDescription("{$user->getNickname()} sent <a href=\"/{$owner->getNickname()}/{$origin_repo->getName()}/issues/{$id}\">pull request #{$id}</a> on {$origin->getNickname()}/{$repository->getName()}");
				$a->setSenderId($user->getKey());
				$a->create();
			}
			header("Location: /{$origin->getNickname()}/{$repository->getName()}/pulls");
		} else {
			$this->render("new.htm",array(
						'user' => $user,
						'owner' => $owner,
						'origin' => $origin,
						'ref' => $params['ref'],
						'repository' => $repository,
			));
		}
	}
}