<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class PullRequestController extends GitHQ\Bundle\AbstractController
{
	
	/**
	* @Controller(newtype=true)
	*/
	public function onFiles($user, $repository, $id)
	{
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		$ref = $issue->getRef();
		$user = $this->getUser();
		
		
		$requestor = User::get($ref['owner']);
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
			'user'       => $user,
			'owner'      => $owner,
			'repository' => $repository,
			'issue'      => $issue,
			'diff'       => $struct,
		));
	}


	/**
	 * @Controller(newtype=true)
	 */	
	public function onClose($user, $repository, $id)
	{
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		$ref = $issue->getRef();
		$user = $this->getUser();
		
		$requestor = User::get($ref['owner']);
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
		system("mkdir -p /home/git/workdir/{$owner->getKey()}/{$repository->getId()}");
		system("chmod 777 -R /home/git/workdir/{$owner->getKey()}/{$repository->getId()}");
		system("git clone -l /home/git/repositories/{$owner->getKey()}/{$repository->getId()} /home/git/workdir/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()}");
		chdir("/home/git/workdir/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()}/");
		system("git pull /home/git/repositories/{$ref['owner']}/{$ref['repository']}",$ret);
		if ($ret === 0) {
			system("git push origin master",$ret);
			chdir("/");
			system("rm -rf /home/git/workdir/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()}");
				
			$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$id)));
			$issue->setStatus(Issue::CLOSED);
			$issue->save();
			
			return new RedirectResponse($this->get('application.url'));
		} else {
			echo "Something went to wrong. please merge manually.";
		}
		
	}

	/**
	* @Controller(newtype=true)
	*/
	public function onPullRequest($user, $repository, $id)
	{
		$owner = User::get(User::getIdByNickname($user));
		$repository_name = $repository;
		$repository = $owner->getRepository($repository);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		$ref = $issue->getRef();
		$user = $this->getUser();
		
		$requestor = User::get($ref['owner']);
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
		system("mkdir -p /tmp/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()}");
		system("git clone -l /home/git/repositories/{$owner->getKey()}/{$repository->getId()} /tmp/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()} 2>");
		system("GIT_DIR=/tmp/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()} git pull /home/git/repositories/{$ref['owner']}/{$ref['repository']} 2>",$ret);
		system("rm -rf /tmp/{$owner->getKey()}/{$repository->getId()}/{$issue->getId()}");
		
		$this->render("pullrequest.htm",array(
					"user"         => $user,
					"issue"        => $issue,
					"owner"        => $owner,
					"repository"   => $repository,
					'requestor'    => $requestor,
					'req_repo'     => $req_repo,
					'commits'      => $res,
					'ret'          => $ret,
					'issue_count'  => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($repository)->getId()),
		));
	}
	
	/**
	* @Controller(newtype=true)
	*/
	public function onNew($user, $repository, $ref)
	{
		$owner = User::getByNickname($user);
		$repository_name = $repository;
		$repository = $owner->getRepository($repository);
		$user = $this->getUser();
		$request = $this->get('request');
		
		
		$origin = User::get($repository->getOrigin());
		$origin_repo = $origin->getRepository($repository_name);
		
		if($this->getRequest()->isPost()) {
			$id = IssueReferences::getNextId($origin->getKey(),$origin_repo->getId());

			$issue = new Issue(join(':',array($origin->getKey(),$origin_repo->getId(),$id)));
			$issue->setId($id);
			$issue->setOwner($origin->getKey());
			$issue->setRepositoryId($origin_repo->getId());
			$issue->setAuthor($user->getKey());
			$issue->setTitle($request->get('title'));
			$issue->setBody($request->get('contents'));
			$issue->setPullrequest();
			$issue->attachRef($user->getKey(), $request->get('ref'),$repository->getId());
			
			if ($issue->create()) {
				$a = new Activity(Activity::getNextId(),'activity');
				$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
				$a->setDescription("{$user->getNickname()} sent <a href=\"/{$owner->getNickname()}/{$origin_repo->getName()}/issues/{$id}\">pull request #{$id}</a> on {$origin->getNickname()}/{$repository->getName()}");
				$a->setSenderId($user->getKey());
				$a->create();
			}
			return new RedirectResponse($this->get('application.url') . "/{$origin->getNickname()}/{$repository->getName()}/pulls");
		} else {
			
			$this->render("new.htm",array(
						'user'       => $user,
						'owner'      => $owner,
						'origin'     => $origin,
						'ref'        => $ref,
						'repository' => $repository,
			));
		}
	}
}