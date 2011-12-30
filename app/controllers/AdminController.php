<?php
use UIKit\Framework\HTTPFoundation\Response\RedirectResponse;

class AdminController extends GitHQ\Bundle\AbstractController
{
	/**
	 * show repsoitory admin page.
	 * 
	 * @param string $nickname
	 * @param string $repository
	 */
	public function onDefault($nickname, $repository)
	{
		$owner = User::getByNickname($nickname);
		$this->render("index.htm",array(
					'owner'      => $owner,
					'repository' => $owner->getRepository($repository),
		));
	}

	/**
	* update specified repository settings
	*
	* @param string $nickname
	* @param string $repository
	*/
	public function onUpdate($nickname, $repository)
	{
		$request = $this->get('request');
		if ($request->isPost()){
			$owner = User::fetchLocked(User::getIdByNickname($nickname));
			$repo = $owner->getRepository($repository);
			
			if ($request->has('features')) {
				if ($request->get('issues') == 1) {
					$repo->enableIssue();
				} else {
					$repo->disableIssue();
				}
			}
			if (strlen($request->get('default_branch'))) {
				$repo->setDefaultBranch($request->get("default_branch"));
			}

			$repo->setStatus($request->get('visibility'));			
			$owner->save();
			
			return new RedirectResponse($this->get("application.url") . "/{$owner->getNickname()}/{$repo->getName()}/admin");
		}
	}

	/**
	* update specified repository settings
	*
	* @param string $nickname
	* @param string $repository
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
		return new RedirectResponse($this->get("application.url"));
	}
}