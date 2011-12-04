<?php
class IssueReferences
{
	public static function getNextId($owner_id,$repository_name)
	{
		$redis = GitHQController::getRedisClient();
		return $redis->incr("sequence.issues.{$owner_id}.{$repository_name}");
	}
	
	public static function pushList($owner,$repository,$id)
	{
		$redis = GitHQController::getRedisClient();
		return $redis->lpush("issue_list.{$owner}.{$repository}",$id);		
	}
	
	public static function getList($owner,$repository,$status,$start="+inf",$end="-inf")
	{
		$redis = GitHQController::getRedisClient();
		return $redis->zRevRangeByScore("issue_list.{$owner}.{$repository}.{$status}",$start,$end);
	}
	
	public static function getOpenedIssueCount($owner,$repository,$status = Issue::OPENED)
	{
		$redis = GitHQController::getRedisClient();
		return $redis->zCard("issue_list.{$owner}.{$repository}.{$status}");
	}
	
	public static function addSubmitedList($owner,$repository,$issue_id,$status,$registered_at)
	{
		$redis = GitHQController::getRedisClient();
		error_log("issue_list.{$owner}.{$repository}.{$status}");
		return $redis->zAdd("issue_list.{$owner}.{$repository}.{$status}",$registered_at,$issue_id);
		
	}
}