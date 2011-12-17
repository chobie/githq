<?php
class Mock_TestCase extends PHPUnit_Framework_Testcase
{
	protected static $redis;
	
	/**
	* setup redis client
	*/
	public static function setupBeforeClass()
	{
		if (defined("REDIS_PORT") && REDIS_PORT == 6379) {
			die("redis port must not use 6379 when executing test case.");
		}
	
		self::$redis = new Redis();
		self::$redis->connect('127.0.0.1',REDIS_PORT);
	}
	
	/**
	 * disconnect redis and flush db.
	 */
	public static function tearDownAfterClass()
	{
		if(self::$redis instanceof \Redis) {
			self::$redis->flushdb();
			self::$redis->close();
		}
	}
	
	/**
	 * flush db when executing each test case.
	 */
	public function setup()
	{
		self::$redis->flushdb();
	}	
}