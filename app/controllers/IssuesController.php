<?php
class IssuesController extends GitHQController
{
	public function onDefault($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		
		if (isset($_REQUEST['milestone'])) {
			$milestone = $repository->getMilestone($_REQUEST['milestone']);
			$list = IssueReferences::getListWithMilestone($milestone, $owner->getKey(), $repository->getName(),Issue::OPENED);
				
		} else if (isset($_REQUEST['label'])) {
			$label = $repository->getLabels()->getLabelByName($_REQUEST['label']);
			$list = IssueReferences::getListWithLabel($label->getId(), $owner->getKey(), $repository->getName(),Issue::OPENED);
		} else {
			$list = IssueReferences::getList($owner->getKey(),$repository->getName(),Issue::OPENED);
		}
		$issues = array();
		foreach ($list as $id) {
			$issues[] = Issue::get(join(':',array($owner->getKey(),$repository->getName(),$id)),'issue');
		}
		
		$this->render("index.htm",array(
			'user' => $user,
			'owner' => $owner,
			'issues' => $issues,
			'repository' => $owner->getRepository($params['repository'])
		));
	}

	public function onNew($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		if($this->getRequest()->isPost()) {
			$id = IssueReferences::getNextId($owner->getKey(),$repository->getName());
			$issue = new Issue(join(':',array($owner->getKey(),$repository->getName(),$id)));
			$issue->setId($id);
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
		
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getName(),$params['id'])),'issue');

		$this->render("issue.htm",array(
			"user" => $user,
			"issue" => $issue,
			"owner" => $owner,
			"repository" => $repository,
		));
	}
	
	public function onUpdate($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getName(),$_REQUEST['id'])),'issue');

		if (isset($_REQUEST['label_delete'])) {
			$issue->removeLabelId($_REQUEST['label']);			
		}

		$issue->save();
		header("Location: /{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
	}
	
	public function onIssueComments($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getName(),$_REQUEST['issue'])),'issue');
		$issue->addComment($user->getKey(), $_REQUEST['comment']);
		if (isset($_REQUEST['close'])) {
			$issue->closeIssue();
		}
		if (isset($_REQUEST['open'])) {
			$issue->openIssue();
		}
		if (isset($_REQUEST['label']) && !empty($_REQUEST['label'])) {
			$labels = $repository->getLabels();
			$label = $labels->getLabelByName($_REQUEST['label']);
			if($label == false) {
				$owner = User::fetchLocked(UserPointer::getIdByNickname($params['user']),'user');
				$repository = $owner->getRepository($params['repository']);
				$labels = $repository->getLabels();
				$next = $labels->getNextId();
				$label = new Label();
				$label->setId($next);
				$label->setName($_REQUEST['label']);

				$labels->addLabel($label);
				$owner->save();
			}
			$issue->addLabelId($label->getId());
		}

		$issue->save();
		header("Location: /{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
	}
	
	public function onEdit($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getName(),$params['id'])),'issue');
		
		if (isset($_REQUEST['update'])) {
			$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getName(),$params['id'])),'issue');
			$issue->setTitle($_REQUEST['title']);
			$issue->setBody($_REQUEST['contents']);
			if (!empty($_REQUEST['milestone'])) {
				$issue->setMilestone($_REQUEST['milestone']);
			} else {
				$issue->removeMilestone();
			}
			$issue->save();

			if (!$repository->getMilestone($_REQUEST['milestone'])) {
				$owner = User::fetchLocked(UserPointer::getIdByNickname($params['user']),'user');
				$repository = $owner->getRepository($params['repository']);
				$repository->addMilestone($_REQUEST['milestone']);
				$owner->save();
			}
			
			header("Location: /{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
		}
		
		$this->render("edit.htm",array(
					"user" => $user,
					"issue" => $issue,
					"owner" => $owner,
					"repository" => $repository,
		));
	}
}