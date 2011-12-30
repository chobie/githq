<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class IssuesController extends GitHQ\Bundle\AbstractController
{
	/**
	* show repsoitory admin page.
	*
	* @param string $nickname
	* @param string $repository
	*/
	public function onDefault($nickname, $repository_name)
	{
		$owner      = User::getByNickname($nickname);
		$repository = $owner->getRepository($repository_name);
		$request    = $this->get('request');
		
		if ($request->has('milestone')) {
			$milestone = $repository->getMilestones()->getMilestoneByName($request->get('milestone'));
			$list      = IssueReferences::getListWithMilestone($milestone->getId(), $owner->getKey(), $repository->getId(),Issue::OPENED);
		} else if ($request->has('label')) {
			$label = $repository->getLabels()->getLabelByName($request->get('label'));
			$list  = IssueReferences::getListWithLabel($label->getId(), $owner->getKey(), $repository->getId(),Issue::OPENED);
		} else {
			$list  = IssueReferences::getList($owner->getKey(),$repository->getId(),Issue::OPENED);
		}
		
		$issues = array();
		foreach ($list as $id) {
			$issues[] = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		}
		
		$this->render("index.htm",array(
			'owner'       => $owner,
			'issues'      => $issues,
			'repository'  => $repository,
			'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'watcher'     => Repository::getWatchedCount($owner, $repository),
		));
	}

	public function onNew($user, $repository)
	{
		$owner = User::get(User::getIdByNickname($user),'user');
		$repository = $owner->getRepository($repository);
		$user = $this->getUser();
		$request = $this->get('request');
		
		if($request->isPost()) {
			$id = IssueReferences::getNextId($owner->getKey(),$repository->getId());
			$issue = new Issue(join(':',array($owner->getKey(),$repository->getId(),$id)));
			$issue->setId($id);
			$issue->setOwner($owner->getKey());
			$issue->setRepositoryId($repository->getId());
			$issue->setAuthor($user->getKey());
			$issue->setTitle($request->get('title'));
			$issue->setBody($request->get('contents'));
			if ($issue->create()) {
				$this->get('event')->emit(new UIKit\Framework\Event('issue.create',array($issue,$user,$owner,$repository)));
			}
			return new RedirectResponse($this->get('appilcation.url') ."/{$owner->getNickname()}/{$repository->getName()}/issues");
		} else {
			$this->render("new.htm",array(
				'owner'      => $owner,
				'repository' => $repository,
			));
		}
	}

	public function onIssue($user, $repository,$id)
	{
		$owner      = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));

		if ($issue->isPullrequest()) {
			return new RedirectResponse($this->get('application.url') . "/{$owner->getNickname()}/{$repository->getName()}/pull/{$issue->getId()}");
		}

		$this->render("issue.htm",array(
			"issue"       => $issue,
			"owner"       => $owner,
			"repository"  => $repository,
			'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'watcher'     => Repository::getWatchedCount($owner, $repository),
		));
	}
	
	public function onUpdate($user, $repository)
	{
		$request    = $this->get('request');
		$owner      = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$issue      = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$request->get('id'))));

		if ($request->has('label_delete')) {
			$issue->removeLabelId($request->get('label'));			
		}

		$issue->save();
		
		return new RedirectResponse($this->get('appilcation.url') ."/{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
	}

	public function onIssueComments($user, $repository)
	{
		$owner = User::get(User::getIdByNickname($user));
		$user_name = $user;
		$user = $this->getUser();
		$repository_name = $repository;
		
		$request = $this->get('request');
		$repository = $owner->getRepository($repository);
		$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$request->get('issue'))));
		$issue->addComment($user->getKey(), $request->get('comment'));
		
		if ($request->has('close')) {
			$issue->closeIssue();
		} else if ($request->has('open')) {
			$issue->openIssue();
		}
		
		if ($request->has('label') && $request->get('label')) {
			$labels = $repository->getLabels();
			$label = $labels->getLabelByName($request->get('label'));
			if($label == false) {
				$owner = User::fetchLocked(User::getIdByNickname($user_name));
				$repository = $owner->getRepository($repository_name);
				$labels = $repository->getLabels();
				$next = $labels->getNextId();
				$label = new Label();
				$label->setId($next);
				$label->setName($request->get('label'));

				$labels->addLabel($label);
				$owner->save();
			}
			$issue->addLabelId($label->getId());
		}
		
		if ($request->has("assign")) {
			$issue->setAssignee(User::getIdByNickname($request->get("assign")));
		}

		if($issue->save()) {
			$this->get('event')->emit(new UIKit\Framework\Event('issue.comment.add',array($issue,$user,$owner,$repository)));
		}
		
		if ($issue->isPullrequest()) {
			return new RedirectResponse($this->get('application.url') . "/{$owner->getNickname()}/{$repository->getName()}/pull/{$issue->getId()}");
		} else {
			return new RedirectResponse($this->get('application.url') . "/{$owner->getNickname()}/{$repository->getName()}/issue/{$issue->getId()}");
		}
	}

	public function onEdit($user, $repository, $id)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		
		if ($request->has('update')) {
			$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$id)));
			$issue->setTitle($request->get('title'));
			$issue->setBody($request->get('contents'));
			if (!$request->get('milestone')) {
				$milestones = $repository->getMilestones();
				if (($milestone = $milestones->getMilestoneByName($request->get('milestone'))) == false) {
					
					$owner = User::fetchLocked(User::getIdByNickname($user));
					$repository = $owner->getRepository($repository_name);
					$id = $repository->getMilestones()->getNextId();
					$milestone = new Milestone();
					$milestone->setId($id);
					$milestone->setName($request->get('milestone'));
					$repository->getMilestones()->addMilestone($milestone);
					$owner->save();
						
				}
				$issue->setMilestoneId($milestone->getId());
			} else {
				$issue->removeMilestone();
			}
			$issue->save();

			if (!$request->get('milestone') && !$repository->getMilestones()->getMilestoneByName($request->get('milestone'))) {
				$owner = User::fetchLocked(User::getIdByNickname($nickname));
				$repository = $owner->getRepository($repository_name);
				
				$milestone = new Milestone();
				$milestone->setId($repository->getMilestones()->getNextId());
				$milestone->setName($request->get('milestone'));
				$repository->getMilestones()->addMilestone($milestone);
				$owner->save();
			}
			
			return new RedirectResponse($this->get('application.url') . "/{$owner->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
		}
		
		$this->render("edit.htm",array(
					"issue"      => $issue,
					"owner"      => $owner,
					"repository" => $repository,
		));
	}
	
	public function onAdmin($user, $repository)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		
		if ($request->isPost()) {
			$l_user = User::fetchLocked(User::getIdByNickname($nickname));
			$repository = $l_user->getRepository($repository_name);
			
			foreach ($repository->getLabels() as $label) {
				$label->setName($_REQUEST['name'][$label->getId()]);
			}
			$l_user->save();
		}
		
		$this->render("admin.htm",array(
							"issue"      => $issue,
							"owner"      => $owner,
							"repository" => $repository,
		));
	}
}