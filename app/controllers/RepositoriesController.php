<?php
class RepositoriesController extends GitHQController
{
	
	public function onDefault()
	{
		if($this->getRequest()->isPost()){ 
			$project_name = $_REQUEST['project_name'];
			$description  = $_REQUEST['description'];
			$homepage_url = $_REQUEST['homepage_url'];
			
			$repo = new Repository($project_name);
			$repo->setDescription($description);
			$repo->setDescription($homepage_url);
			$user = $this->getUser();
			
			if ($repo->create($user->getNickname())) {
				$user = User::fetchLocked($_SESSION['user']->getKey(),"user");
				$user->addRepository($repo);
				if ($_REQUEST['visibility'] == 1) {
					$repo->setPrivate();
				}
				$user->save();
				$_SESSION['user'] = $user;
				header("Location: http://githq.org");
			}
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
