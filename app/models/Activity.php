<?php
/**
 * githq activity model
 * 
 * @author chobie
 */
class Activity extends \UIKit\Framework\UIStoredObject
{
	const VISIBILITY_PUBLIC       = 0x0;
	const VISIBILITY_PRIVATE      = 0x01;
	const VISIBILITY_ORGANIZATION = 0x02;
	
	const KEY_TIMELINE_GLOBAL = 'timeline.global';
	const KEY_TIMELINE_USERS  = 'timeline.users';
	const KEY_SEQUENCE        = 'sequence.timeline';
	
	protected $sender_id;
	protected $image_url;
	protected $description;
	protected $visibility;
	
	/**
	 * set image url.
	 * 
	 * @param string $image_url
	 */
	public function setImageUrl($image_url)
	{
		$this->image_url = $image_url;
	}
	
	/**
	 * get activity image url
	 * @return string $image_url;
	 */
	public function getImageUrl()
	{
		return $this->image_url;
	}
	
	/**
	 * get current activity key
	 * @return string $key
	 */
	public function getKey()
	{
		return $this->key;
	}
	
	/**
	 * get timeline with specified user id.
	 * 
	 * @param string $user_id
	 * @return array $timeline
	 */
	public static function getTimelineByUserId($user_id)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$list = $redis->lrange(self::KEY_TIMELINE_USERS . ".{$user_id}",0,100);
		$result = Activity::mget($list, 'activity');
		return $result;
	}
	
	/**
	 * get global timeline.
	 * @return array $timeline
	 */
	public static function getGlobalTimeline()
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$list = $redis->lrange(self::KEY_TIMELINE_GLOBAL,0,100);
		$result = Activity::mget($list, 'activity');
		return $result;
	}
	
	/**
	 * get nect activity id.
	 * @return int $id
	 */
	public static function getNextId()
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->incr(self::KEY_SEQUENCE);
	}
	
	/**
	 * get sender id
	 * @return string $sender_id
	 */
	public function getSenderId()
	{
		return $this->sender_id;
	}
	
	/**
	 * set sender id.
	 * 
	 * @param int $id
	 */
	public function setSenderId($id)
	{
		$this->sender_id = $id;
	}
	
	/**
	 * set activity description 
	 * 
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->description = $description;
	}
	
	/**
	 * get description
	 * @return string $description
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::type()
	 */
	public function type()
	{
		return 'activity';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::create()
	 */
	public function create(\Closure $closure = null)
	{
		$senderId = $this->getSenderId();
		if ($senderId === null || $senderId === false) {
			throw new \InvalidArgumentException("Activity requires senderId");
		}
		
		return parent::create(function($stmt,$activity){
			$stmt->lpush(Activity::KEY_TIMELINE_GLOBAL,$activity->getKey());
			$stmt->ltrim(Activity::KEY_TIMELINE_GLOBAL,0,100);
			$stmt->lpush(Activity::KEY_TIMELINE_USERS . ".{$activity->getSenderId()}",$activity->getKey());
		});
	}
}