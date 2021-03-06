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
		} else if ($request->get('filter_by') == "assigned") {
			$user = $this->getUser();
			$list  = IssueReferences::getListWithAssigned($user->getKey(), $owner->getKey(), $repository->getId(),Issue::OPENED);
		} else {
			$list  = IssueReferences::getList($owner->getKey(),$repository->getId(),Issue::OPENED);
		}
		
		$issues = array();
		foreach ($list as $id) {
			$issues[] = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		}
		
		$user = $this->getUser();
		if ($user){
			$to = IssueReferences::getAssignedToYouCount($owner->getKey(), $repository->getId(),Issue::OPENED, $user->getKey());
		} else {
			$to = 0;
		}
		
		$this->render("index.htm",array(
			'owner'           => $owner,
			'issues'          => $issues,
			'repository'      => $repository,
			'issue_count'     => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'watcher'         => Repository::getWatchedCount($owner, $repository),
			'assigned_to_you' => $to,
			'tab'             => 'issue'
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
			return new RedirectResponse($this->generateUrl('issues',array(
				"nickname"        => $owner->getNickname(),
				"repository_name" => $repository->getName(),
			)));
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
			return new RedirectResponse($this->generateUrl('pull.show',array(
				"user"       => $owner->getNickname(),
				"repository" => $repository->getName(),
				"id"         => $issue->getId(),
			)));
		}

		$this->render("issue.htm",array(
			"issue"       => $issue,
			"owner"       => $owner,
			"repository"  => $repository,
			'issue_count' => IssueReferences::getOpenedIssueCount($owner->getKey(), $repository->getId()),
			'watcher'     => Repository::getWatchedCount($owner, $repository),
			'vote'        => IssueReferences::getVoteCount($owner->getKey(), $repository->getId(), $id),
			'tab'         => 'issue',
			'members'     => IssueReferences::getVotedMembers($owner->getKey(), $repository->getId(), $id),
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
		
		return new RedirectResponse($this->generateUrl('show_issue',array(
			"user"       => $owner->getNickname(),
			"repository" => $repository->getName(),
			"id"         => $issue->getId(),
		)));
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
		if (strlen($request->get('comment'))){
			$issue->addComment($user->getKey(), $request->get('comment'));
		}
		
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
		
		if ($request->has("assign") && strlen($request->get("assign"))) {
			$issue->setAssignee(User::getIdByNickname($request->get("assign")));
		}

		if($issue->save() && strlen($request->get('comment'))) {
			$this->get('event')->emit(new UIKit\Framework\Event('issue.comment.add',array($issue,$user,$owner,$repository)));
		}
		
		return new RedirectResponse($this->generateUrl(($issue->isPullrequest()) ? "pull.show" : "show_issue",array(
						"user"       => $owner->getNickname(),
						"repository" => $repository->getName(),
						"id"         => $issue->getId(),
		)));
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
			if (strlen($request->get('milestone'))) {
				$milestones = $repository->getMilestones();
				if (strlen($request->get('milestone')) && ($milestone = $milestones->getMilestoneByName($request->get('milestone'))) == false) {
					
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
			
			return new RedirectResponse($this->generateUrl('show_issue',array(
						"user"       => $owner->getNickname(),
						"repository" => $repository->getName(),
						"id"         => $issue->getId(),
			)));
		}
		
		$this->render("edit.htm",array(
					"issue"      => $issue,
					"owner"      => $owner,
					"repository" => $repository,
		));
	}
	
	public function onAdmin($user, $repository)
	{
		if (!$this->getUser()) {
			new RedirectResponse($this->get('application.url'));
		}
		
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

			foreach ($repository->getMilestones() as $milestone) {
				$milestone->setName($_REQUEST['mname'][$milestone->getId()]);
			}

			$l_user->save();
		}
		
		$this->render("admin.htm",array(
							"issue"      => $issue,
							"owner"      => $owner,
							"repository" => $repository,
		));
	}
	
	public function onVote($user, $repository,$id)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$user = $this->getUser();
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));

		if ($user) {
			$issue->vote($user);
		}

		return new RedirectResponse($this->generateUrl('show_issue',array(
					"user"       => $owner->getNickname(),
					"repository" => $repository->getName(),
					"id"         => $issue->getId(),
		)));
	}

	public function onUnvote($user, $repository,$id)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$user = $this->getUser();
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		if ($user) {
			$issue->unvote($user);
		}

		return new RedirectResponse($this->generateUrl('show_issue',array(
							"user"       => $owner->getNickname(),
							"repository" => $repository->getName(),
							"id"         => $issue->getId(),
		)));
	}
	
	public function onVoteComment($user, $repository,$id,$offset)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$user = $this->getUser();
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		if ($user) {
			$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$id)));
			$comments = $issue->getComments();
			foreach ($comments as $k => $i){
				if ($k == $offset) {
					$i->vote($user);
				}
			}
			$issue->save();
		}
		
		return new RedirectResponse($this->generateUrl('show_issue',array(
							"user"       => $owner->getNickname(),
							"repository" => $repository->getName(),
							"id"         => $issue->getId(),
		)));
	}

	
	public function onEditComment($user, $repository, $id, $offset)
	{
		$nickname = $user;
		$repository_name = $repository;
		$owner = User::get(User::getIdByNickname($user));
		$repository = $owner->getRepository($repository);
		$request = $this->get('request');
		$issue = Issue::get(join(':',array($owner->getKey(),$repository->getId(),$id)));
		$comment = $issue->getComment($offset);		
		
		if ($request->has('update')) {
			$issue = Issue::fetchLocked(join(':',array($owner->getKey(),$repository->getId(),$id)));
			$comment = $issue->getComment($offset);
			if ($comment) {
				$comment->setComment($request->get('contents'));
			}
			$issue->save();

			return new RedirectResponse($this->generateUrl('show_issue',array(
										"user"       => $owner->getNickname(),
										"repository" => $repository->getName(),
										"id"         => $issue->getId(),
			)));
		}
	
		$this->render("edit_comment.htm",array(
						"issue"      => $issue,
						"comment"    => $comment,
						"owner"      => $owner,
						"offset"     => $offset,
						"repository" => $repository,
		));
	}
	
}