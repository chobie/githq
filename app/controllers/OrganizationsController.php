<?php
class OrganizationsController extends GitHQ\Bundle\AbstractController
{
	
	public function onDefault($params)
	{
		$user = $this->getUser();
		
		$organization = User::get(User::getIdByNickname($params['organization']),'user');
		
		$timeline = Activity::getGlobalTimeline();
		$this->render("index.htm",array(
						'user'=>$user,
						'organization' => $organization,
						'timeline' => $timeline,
						'organizations' => $user->getJoinedOrganizations(),
		));
	}
	
	public function onNew($params)
	{
		if ($this->getRequest()->isPost()) {
			$project_name = $_REQUEST['project_name'];
			$description  = $_REQUEST['description'];
			$homepage_url = $_REQUEST['homepage_url'];
			
			$user = User::fetchLocked(User::getIdByNickname($params['organization']),"user");
			$repo = new Repository($project_name);
			$id = $user->getNextRepositoryId();
			error_log("repository_id: {$id}");
			$repo->setId($id);
			$repo->setDescription($description);
			$repo->setHomepageUrl($homepage_url);
				
			if ($repo->create($user->getKey())) {
				$user->addRepository($repo);
				if ($_REQUEST['visibility'] == 1) {
					$repo->setPrivate();
				} else {
					$a = new Activity(Activity::getNextId(),'activity');
					$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
					$a->setDescription("{$user->getNickname()} created <a href=\"/{$user->getNickname()}/{$repo->getName()}\">{$user->getNickname()}/{$repo->getName()}</a>");
					$a->setSenderId($user->getKey());
					$a->create();
				}
			}
			$user->save();
			header("Location: http://githq.org/");
			exit;
		}
		
		$this->render("new.htm",array(
			'organization' => $params['organization']
		));
	}
}