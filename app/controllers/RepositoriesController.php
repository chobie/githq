<?php
class RepositoriesController extends UIkit\Framework\UIAppController
{
	
	public function onDefault()
	{
		if($this->is_post()){ 
			$project_name = $_REQUEST['project_name'];
			$description  = $_REQUEST['description'];
			$homepage_url = $_REQUEST['homepage_url'];
			
			$repo = new Repository($project_name);
			$repo->setDescription($description);
			$repo->setDescription($homepage_url);
			if ($repo->create()) {
				$user = User::fetchLocked($_SESSION['user']->getKey(),"user");
				$user->addRepository($repo);
				$user->save();
				$_SESSION['user'] = $user;
				header("Location: http://githq.org");
			}
		}
	}

	public function onNew()
	{
		$this->render("new.htm",array());
	}
	
}
