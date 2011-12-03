<?php
class UserPointer
{
	public static function getIdByEmail($email)
	{
		$redis = GitHQController::getRedisClient();
		return $redis->get("pointer.user_id.email.{$email}");
	}
	
	public static function getIdByNickname($nickname)
	{
		$redis = GitHQController::getRedisClient();
		return $redis->get("pointer.user_id.nickname.{$nickname}");
	}
	
	public static function setIdWithNickname($id, $nickname)
	{
		$redis = GitHQController::getRedisClient();
		$redis->set("pointer.user_id.nickname.{$nickname}",$id);
	}
	
	public static function setIdWithEmail($id, $email)
	{
		$redis = GitHQController::getRedisClient();
		$redis->set("pointer.user_id.email.{$email}",$id);
	}
	
	public static function getNextId()
	{
		$redis = GitHQController::getRedisClient();
		return $redis->incr("sequence.user_id");
	}
}