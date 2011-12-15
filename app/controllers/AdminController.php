<?php
class AdminController extends GitHQController
{
	public function onDefault($params)
	{
		$user = $this->getUser();
		$owner = User::get(UserPointer::getIdByNickname($params['user']),'user');
		$this->render("index.htm",array(
					'user'         => $user,
					'owner'        => $owner,
					'repository'   => $owner->getRepository($params['repository']),
		));
	}
	
	public function onUpdate($params)
	{
		$owner = User::fetchLocked(UserPointer::getIdByNickname($params['user']),'user');
		$repo = $owner->getRepository($params['repository']);
		
		$repo->setStatus($_REQUEST['visibility']);
		$owner->save();
		header("Location: http://githq.org/{$owner->getNickname()}/{$repo->getName()}/admin");
		
	}
	
	public function onDelete($params)
	{
		$user = $this->getUser();
		$owner = User::fetchLocked(UserPointer::getIdByNickname($params['user']),'user');
		$repository = $owner->getRepository($params['repository']);
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
		$_SESSION['user'] = $owner;
		header("Location: http://githq.org/");
	}
}