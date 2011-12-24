<?php
namespace GitHQ\Bundle;

abstract class AbstractController extends \UIKit\Framework\HTTPFoundation\Controller\ApplicationController
{
	protected static $redis;
	
	public function __construct($container)
	{
		parent::__construct($container);		
	}
	
	public function getLogger()
	{
		return $this->logger;
	}

	protected function getUser()
	{
		if (isset($_SESSION['user']) && $_SESSION['user'] instanceof \User) {
			return $_SESSION['user'];
		} else {
			return null;
		}
	}

	public static function getRedisClient()
	{
		if(!isset(self::$redis)) {
			self::$redis = new \Redis();
			$cfg = \UIKit\Framework\ObjectStore\Config::getInstance();
			$func = "connect";
			if ($cfg->get(join('.',array('user',"redis","persistence")))) {
				$func = "pconnect";
			}
			call_user_func_array(array(self::$redis,$func),array(
			$cfg->get(join('.',array('user',"redis","host")),\UIKit\Framework\ObjectStore\Driver\Redis::DEFAULT_HOST),
			$cfg->get(join('.',array('user',"redis","port")),\UIKit\Framework\ObjectStore\Driver\Redis::DEFAULT_PORT)
			));
		}
		return self::$redis;
	}
}