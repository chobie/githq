<?php
require __DIR__ . '/../config/env.php';

class GitHQApplicationDelegate extends UIKit\Framework\HTTPFoundation\WebApplication\Delegate
{	
	protected function getRouting()
	{
		$router = new UIKit\Framework\HTTPFoundation\Router();
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
		$dispatcher = new UIKit\Framework\HTTPFoundation\RoutingResolver($this->getRouting());
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
UIKit\Framework\UIWebApplicationMain(null,null,'UIKit\Framework\HTTPFoundation\WebApplication','GitHQApplicationDelegate');