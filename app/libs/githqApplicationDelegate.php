<?php
class githqApplicationDelegate extends UIKit\Framework\HTTPFoundation\WebApplication\Delegate
{	
	
	public function registerContainer($container)
	{
		$this->container = $container;
	}
	
	public function getContainer()
	{
		return $this->container;
	}
	
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
		$container = new UIKit\Framework\DependencyInjection\Container();
		$container->setLoader(new UIKit\Framework\DependencyInjection\Loader\XMLFileLoader());
		$container->load(__DIR__ . "/../config/config.xml");
		$this->registerContainer($container);
		
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