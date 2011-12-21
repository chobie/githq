<?php
require __DIR__ . '/../config/env.php';

class GitHQApplicationDelegate extends UIKit\Framework\UIWebApplicationDelegate
{
	public function didBecomeActive()
	{
		$data = file_get_contents(__DIR__ . "/../config/entities.xml");
		$xml = simplexml_load_string($data);
		
		$result = array();
		foreach($xml->xpath("//entity") as $element) {
			$id = (string)$element->attributes()->id;
			$result[$id]['strategy']      = (string)$element->strategy;
			$result[$id]['serializer']   = (string)$element->serializer;
			$result[$id]['cache']        = (string)$element->cache;
			$result[$id]['expiration']   = (string)$element->expiration;
			$result[$id]['lock_timeout'] = (string)$element->lock_timeout;

			foreach ($element->redis as $redis) {
				$result[$id]['redis'] = array(
					"host" => (string)$redis->host,
					"port" => (string)$redis->port,
					"persistence" => (string)$redis->persistence,
				);
			}
		}
		
		$conf= UIKit\Framework\UIStoredConfig::getInstance();
		foreach($result as $key => $config) {
			$conf->set($key,$config);
		}

		$i = UIKit\Framework\UIStoredUnderlying::getInstance();
		foreach ($conf->keys() as $key) {
			$i->addStrategy($key, $conf->get($key . ".strategy"));
			$i->addSerializer($key, $conf->get($key . ".serializer"));
			$i->addCache($key,$conf->get($key . '.cache'));
		}
	}
	
	protected function getRouting()
	{
		$router = new UIKit\Framework\UIRouter();
		$data = file_get_contents(__DIR__ . "/../config/routes.xml");
		$xml = simplexml_load_string($data);
		
		foreach($xml->xpath("//route") as $element) {
			$defaults = array();
			foreach($element->default as $item) {
				$defaults[(string)$item->attributes()->key] = (string)$item[0];
			}
			$router->add((string)$element->attributes()->pattern,$defaults);
		}
		return $router;
	}
	
	public function didFinishLaunchingWithOptions($app, $options = array())
	{
		$dispatcher = new UIKit\Framework\UIUrlDispatcher($this->getRouting());
		$result = $dispatcher->dispatch();
		
		$this->controller = $result['controller'];
		$this->action     = $result['action'];
		$this->parameters = $result['parameters'];
	
		if(class_exists($this->controller)){
			return true;
		} else {
			return false;
		}
	}	
}

session_start();
UIKit\Framework\UIWebApplicationMain(null,null,'UIKit\Framework\UIWebApplication','GitHQApplicationDelegate');