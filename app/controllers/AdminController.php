<?php
class AdminController extends GitHQ\Bundle\AbstractController
{
	/**
	 * show repsoitory admin page.
	 * 
	 * @param string $nickname
	 * @param string $repository
	 * @Controller(newtype=true)
	 */
	public function onDefault($nickname, $repository)
	{
		$user  = $this->getUser();
		$owner = User::getByNickname($nickname);
		
		$this->render("index.htm",array(
					'user'         => $user,
					'owner'        => $owner,
					'repository'   => $owner->getRepository($repository),
		));
	}

	/**
	* update specified repository settings
	*
	* @param string $nickname
	* @param string $repository
	* @Controller(newtype=true)
	*/
	public function onUpdate($nickname, $repository)
	{
		if ($this->getRequest()->isPost()){
			$owner = User::fetchLocked(User::getIdByNickname($nicknmae));
			$repo = $owner->getRepository($repository);
			
			if (isset($_REQUEST['features'])) {
				if ($this->getRequest()->get('issues') == 1) {
					$repo->enableIssue();
				} else {
					$repo->disableIssue();
				}
			} else {
				$repo->setStatus($_REQUEST['visibility']);
			}
			
			$owner->save();
			header("Location: http://githq.org/{$owner->getNickname()}/{$repo->getName()}/admin");
		}
	}

	/**
	* update specified repository settings
	*
	* @param string $nickname
	* @param string $repository
	* @Controller(newtype=true)
	*/
	public function onDelete($nickname, $repository_name)
	{
		$user = $this->getUser();
		
		$owner = User::fetchLocked(User::getIdByNickname($nickname));
		$repository = $owner->getRepository($repository_name);
		$owner->removeRepository($params['repository']);
		
		foreach($this->getRedisClient()->keys("issue_list.{$owner->getKey()}.{$repository->getName()}*") as $key) {
			$this->getRedisClient()->delete($key);
		}
		
		foreach($this->getRedisClient()->keys("issue_labels.{$owner->getKey()}.{$repository->getName()}*") as $key) {
			$this->getRedisClient()->delete($key);
		}
		
		foreach($this->getRedisClient()->keys("issue_milestone.{$owner->getKey()}.{$repository->getName()}*") as $key) {
			$this->getRedisClient()->delete($key);
		}
		
		$owner->save();
		
		//@todo: considering organization repo.
		$_SESSION['user'] = $owner;

		return new UIKit\Framework\HTTPFoundation\Response\RedirectResponse($this->get("application.url"));
	}
}