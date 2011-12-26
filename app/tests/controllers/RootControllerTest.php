<?php
class TestClient
{
	public function __construct()
	{		
	}
	
	public function request($path,$header = array() ,$method="GET")
	{
		$argv = array(
					'env'    => 'test',
					'config' => dirname(__DIR__) . '/config.xml',
					'root'   => dirname(dirname(__DIR__))
		);
		
		$_SERVER['HOST']           = 'githq.org';
		$_SERVER['REQUEST_URI']    = $path;
		$_SERVER['REQUEST_METHOD'] = $method;
		
		$response = \UIKit\Framework\HTTPFoundation\WebApplication\Mock(count($argv),$argv,
					'UIKit\Framework\HTTPFoundation\WebApplication',
					'githqApplicationDelegate'
		);
		return $response;
	}
}

class RootControllerTest extends PHPUnit_Framework_TestCase
{
	public function createClient()
	{
			return new TestClient();
	}
	
	
	public function testOnDefault()
	{
		$response = $this->createClient()->request("/");
		$this->assertEquals($response->getStatusCode(), '200');
	}
}