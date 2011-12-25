<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class IssuesController extends GitHQ\Bundle\AbstractController
{
	/**
	* show repsoitory admin page.
	*
	* @param string $nickname
	* @param string $repository
	* @Controller(newtype=true)
	*/
	public function onDefault($nickname, $repository_name)
	{
		$user       = $this->getUser();
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
			'user'        => $user,
			'owner'       => $owner,
			'issues'      => $issues,
			'repository'  => $owner->getRepository($repository_name),
			'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $owner->getRepository($repository_name)->getId()),
		));
	}

	/**
	* @Controller(newtype=true)
	*/
	public function onNew($user, $repository)
	{
		$owner = User::get(User::getIdByNickname($user),'user');
		$repository = $owner->getRepository($repository);
		$user = $this->getUser();
		
		if($this->get('request')->isPost()) {
			$id = IssueReferences::getNextId($owner->getKey(),$repository->getId());
			$issue = new Issue(join(':',array($owner->getKey(),$repository->getId(),$id)));
			$issue->setId($id);
			$issue->setOwner($owner->getKey());
			$issue->setRepositoryId($repository->getId());
			$issue->setAuthor($user->getKey());
			$issue->setTitle($_REQUEST['title']);
			$issue->setBody($_REQUEST['contents']);
			if ($issue->create()) {
				$a = new Activity(Activity::getNextId(),'activity');
				$a->setImageUrl("http://www.gravatar.com/avatar/" . md5($user->getEmail()));
				$a->setDescription("{$user->getNickname()} opened <a href=\"/{$owner->getNickname()}/{$repository->getName()}/issues/{$id}\">issue {$id}</a> on {$owner->getNickname()}/{$repository->getName()}");
				$a->setSenderId($user->getKey());
				$a->create();
			}
			return new RedirectResponse($this->get('appilcation.url') ."/{$owner->getNickname()}/{$repository->getName()}/issues");
		} else {
			$this->render("new.htm",array(
				'user' => $user,
				'owner' => $owner,
				'repository' => $repository,
			));
		}
	}

	/**
	* @Controller(newtype=true)
	*/
	public function onIssue($user, $repository,$id)
	{
		$owner      = User::getByNickname($user);
		$repository = $owner->getRepository($repository);
		$user       = $this->getUser();
		
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));

		if ($issue->isPullrequest()) {
			return new RedirectResponse($this->get('application.url') . "/{$owner->getNickname()}/{$repository->getName()}/pull/{$issue->getId()}");
		}

		$this->render("issue.htm",array(
			"user"        => $user,
			"issue"       => $issue,
			"owner"       => $owner,
			"repository"  => $repository,
			'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
		
		));
	}
	
	/**
	* @Controller(newtype=true)
	*/
	public function onUpdate($user, $repository)
	{
		$request    = $this->get('request');
		$owner      = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$issue      = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$request->get('id'))));

		$user = $this->getUser();
		
		if ($request->has('label_delete')) {
			$issue->removeLabelId($request->get('label'));			
		}

		$issue->save();
		
		return new RedirectResponse($this->get('appilcation.url') ."/{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
	}

	/**
	* @Controller(newtype=true)
	*/
	public function onIssueComments($user, $repository)
	{
		$owner = User::get(User::getIdByNickname($user));
		$user_name = $user;
		$repository_name = $repository;
		
		$request = $this->get('request');
		$repository = $owner->getRepository($repository);
		$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$request->get('issue'))));
		$issue->addComment($user->getKey(), $request->get('comment'));
		$user = $this->getUser();
		
		if ($request->has('close')) {
			$issue->closeIssue();
		}
		if ($request->has('open')) {
			$issue->openIssue();
		}
		if ($request->has('label') && !$request->get('label')) {
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

		if($issue->save()) {
			$a = new Activity(Activity::getNextId(),'activity');
			$a->setImageUrl("http://www.gravatar.com/avatar/" . md5($user->getEmail()));
			$a->setDescription("{$user->getNickname()} commented <a href=\"/{$owner->getNickname()}/{$repository->getName()}/issues/{$_REQUEST['issue']}\">issue {$_REQUEST['issue']}</a> on {$owner->getNickname()}/{$repository->getName()}");
			$a->setSenderId($user->getKey());
			$a->create();
		}
		
		if ($issue->isPullrequest()) {
			header("Location: /{$owner->getNickname()}/{$repository->getName()}/pull/{$issue->getId()}");
		} else {
			header("Location: /{$owner->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
		}
	}

	/**
	* @Controller(newtype=true)
	*/
	public function onEdit($user, $repository, $id)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		$user = $this->getUser();
		
		if (isset($_REQUEST['update'])) {
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
			
			return new RedirectResponse($this->get('application.url') . "/{$user->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}");
		}
		
		$this->render("edit.htm",array(
					"user"       => $user,
					"issue"      => $issue,
					"owner"      => $owner,
					"repository" => $repository,
		));
	}
}