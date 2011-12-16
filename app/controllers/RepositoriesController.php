<?php
class RepositoriesController extends GitHQController
{
	
	public function onDefault()
	{
		if($this->getRequest()->isPost()){ 
			$project_name = $_REQUEST['project_name'];
			$description  = $_REQUEST['description'];
			$homepage_url = $_REQUEST['homepage_url'];
			
			$user = User::fetchLocked($_SESSION['user']->getKey(),"user");
			$repo = new Repository($project_name);
			$id = $user->getNextRepositoryId();
			$repo->setId($id);
			$repo->setDescription($description);
			$repo->setDescription($homepage_url);
			
			if ($repo->create($user->getKey())) {
				$user->addRepository($repo);
				if ($_REQUEST['visibility'] == 1) {
					$repo->setPrivate();
				} else {
					$a = new Activity(Activity::getNextId(),'activity');
					$a->setImageUrl("http://www.gravatar.com/avatar/" . md5($user->getEmail()));
					$a->setDescription("{$user->getNickname()} created <a href=\"/{$user->getNickname()}/{$repo->getName()}\">{$user->getNickname()}/{$repo->getName()}</a>");
					$a->setSenderId($user->getKey());
					$a->create();
				}
				$_SESSION['user'] = $user;
			}
			$user->save();
			header("Location: http://githq.org/");
		}
	}

	public function onNew()
	{
		$user = $this->getUser();
		
		$this->render("new.htm",array(
			'user' => $user,
		));
	}
	
}
