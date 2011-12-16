<?php
class Activity extends \UIKit\Framework\UIStoredObject
{
	const VISIBILITY_PUBLIC = 0x0;
	const VISIBILITY_PRIVATE = 0x01;
	const VISIBILITY_ORGANIZATION = 0x02;
	
	protected $sender_id;
	protected $image_url;
	protected $description;
	protected $visibility;
	
	public function setImageUrl($image_url)
	{
		$this->image_url = $image_url;
	}
	
	public function getImageUrl()
	{
		return $this->image_url;
	}
	
	public function getKey()
	{
		return $this->key;
	}
	
	public static function getTimelineByUserId($user_id)
	{
		$redis = GitHQController::getRedisClient();
		$list = $redis->lrange("timeline.users.{$user_id}",0,100);
		$result = Activity::mget($list, 'activity');
		return $result;
	}
	
	public static function getGlobalTimeline()
	{
		$redis = GitHQController::getRedisClient();
		$list = $redis->lrange('timeline.global',0,100);
		$result = Activity::mget($list, 'activity');
		return $result;
	}
	
	public static function getNextId()
	{
		$redis = GitHQController::getRedisClient();
		return $redis->incr('sequence.timeline');
	}
	
	public function getSenderId()
	{
		return $this->sender_id;
	}
	public function setSenderId($id)
	{
		$this->sender_id = $id;
	}
	
	public function setDescription($description)
	{
		$this->description = $description;
	}
	public function getDescription()
	{
		return $this->description;
	}
	
	public function type()
	{
		return 'activity';
	}
	
	public function create()
	{
		return parent::create(function($stmt,$activity){
			$stmt->lpush('timeline.global',$activity->getKey());
			$stmt->ltrim('timeline.global',0,100);
			$stmt->lpush("timeline.users.{$activity->getSenderId()}",$activity->getKey());
		});
	}
}