<?php
class IssuesController extends GitHQController
{
	public function onDefault($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$list = IssueReferences::getList($owner->getKey(),$repository->getName(),Issue::OPENED);
		$issues = array();
		foreach ($list as $id) {
			$issues[] = Issue::get($id,'issue');
		}
		
		$this->render("index.htm",array(
			'user' => $user,
			'owner' => $owner,
			'issues' => $issues,
			'repository' => $user->getRepository($params['repository'])
		));
	}

	public function onNew($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		if($this->getRequest()->isPost()) {
			$id = IssueReferences::getNextId($owner->getKey(),$repository->getName());
			$issue = new Issue($id);
			$issue->setOwner($owner->getKey());
			$issue->setRepository($repository->getName());
			$issue->setAuthor($user->getKey());
			$issue->setTitle($_REQUEST['title']);
			$issue->setBody($_REQUEST['contents']);
			$issue->create();
			header("Location: /{$owner->getNickname()}/{$repository->getName()}/issues");
		} else {
			$this->render("new.htm",array(
				'user' => $user,
				'owner' => $owner,
				'repository' => $repository,
			));
		}
	}
	
	public function onIssue($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::get($params['id'],'issue');

		$this->render("issue.htm",array(
			"user" => $user,
			"issue" => $issue,
			"owner" => $owner,
			"repository" => $repository,
		));
	}
	
	public function onIssueComments($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::fetchLocked($_REQUEST['issue'],'issue');
		$issue->addComment($user->getKey(), $_REQUEST['comment']);
		if (isset($_REQUEST['close'])) {
			$issue->closeIssue();
		}
		if (isset($_REQUEST['label']) && !empty($_REQUEST['label'])) {
			$issue->addLabel($_REQUEST['label']);
		}
		$issue->save();
		header("Location: /{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
	}
}