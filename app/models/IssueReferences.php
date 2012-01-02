<?php
class IssueReferences
{
	public static function getNextId($owner_id,$repository_name)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->incr("sequence.issues.{$owner_id}.{$repository_name}");
	}
	
	public static function pushList($owner,$repository,$id)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->lpush("issue_list.{$owner}.{$repository}",$id);		
	}
	
	public static function getListWithAssigned($user,$owner,$repository,$status,$start="+inf",$end="-inf")
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zRevRangeByScore("issue_assigned.{$owner}.{$repository}.{$user}.{$status}",$start,$end);
	}
	
	public static function getListWithMilestone($milestone,$owner,$repository,$status,$start="+inf",$end="-inf")
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zRevRangeByScore("issue_milestone.{$owner}.{$repository}.{$milestone}.{$status}",$start,$end);
	}
	
	public static function getListWithLabel($label,$owner,$repository,$status,$start="+inf",$end="-inf")
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zRevRangeByScore("issue_labels.{$owner}.{$repository}.{$label}.{$status}",$start,$end);
	}
	
	public static function getList($owner,$repository,$status,$start="+inf",$end="-inf")
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zRevRangeByScore("issue_list.{$owner}.{$repository}.{$status}",$start,$end);
	}
	
	public static function getAssignedToYouCount($owner,$repository,$status = Issue::OPENED, $user)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zCard("issue_assigned.{$owner}.{$repository}.{$user}.{$status}");
	}
	
	public static function getOpenedIssueCount($owner,$repository,$status = Issue::OPENED)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zCard("issue_list.{$owner}.{$repository}.{$status}");
	}
	
	public static function addSubmitedList($owner,$repository,$issue_id,$status,$registered_at)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->zAdd("issue_list.{$owner}.{$repository}.{$status}",$registered_at,$issue_id);
	}

	public static function getVoteCount($owner,$repository,$issue)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->sCard("issue_votes.{$owner}.{$repository}.{$issue}");
	}

	public static function getVotedMembers($owner,$repository,$issue)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->smembers("issue_votes.{$owner}.{$repository}.{$issue}");
	}
}