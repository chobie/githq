<?php
class RepositoryPointer
{
	public static function getNextId()
	{
		$redis = GitHQController::getRedisClient();
		return $redis->incr("sequence.reposiotry");
	}
}