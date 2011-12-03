<?php
abstract class GitHQController extends UIkit\Framework\UIAppController
{
	protected static $redis;

	protected function getUser()
	{
		if (isset($_SESSION['user']) && $_SESSION['user'] instanceof User) {
			return $_SESSION['user'];
		} else {
			return null;
		}
	}


	public static function getRedisClient()
	{
		if(!isset(self::$redis)) {
			self::$redis = new \Redis();
			$cfg = UIKit\Framework\UIStoredConfig::getInstance();
			$func = "connect";
			if ($cfg->get(join('.',array('user',"redis","persistence")))) {
				$func = "pconnect";
			}
			call_user_func_array(array(self::$redis,$func),array(
			$cfg->get(join('.',array('user',"redis","host")),UIKit\Framework\UIStoredRedisStrategy::DEFAULT_HOST),
			$cfg->get(join('.',array('user',"redis","port")),UIKit\Framework\UIStoredRedisStrategy::DEFAULT_PORT)
			));
		}
		return self::$redis;
	}
}