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

	public function didReciveEvent($event)
	{
		if ($event->getName() == 'issue.comment.add') {
			list($issue, $user,$owner, $repository) = $event->getArgs();
			$a = new Activity(Activity::getNextId());
			$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
			$a->setDescription("{$user->getNickname()} commented <a href=\"/{$owner->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}\">issue {$issue->getId()}</a> on {$owner->getNickname()}/{$repository->getName()}");
			$a->setSenderId($user->getKey());
			$a->create();
		} else if ($event->getName() == 'issue.create'){
			list($issue, $user,$owner, $repository) = $event->getArgs();
			$a = new Activity(Activity::getNextId());
			$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
			$a->setDescription("{$user->getNickname()} opened <a href=\"/{$owner->getNickname()}/{$repository->getName()}/issues/{$issue->getId()}\">issue {$issue->getId()}</a> on {$owner->getNickname()}/{$repository->getName()}");
			$a->setSenderId($user->getKey());
			$a->create();
		} else if ($event->getName() == 'repository.new') {
			list($user,$repo) = $event->getArgs();
			$a = new Activity(Activity::getNextId());
			$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
			$a->setDescription("{$user->getNickname()} created <a href=\"/{$user->getNickname()}/{$repo->getName()}\">{$user->getNickname()}/{$repo->getName()}</a>");
			$a->setSenderId($user->getKey());
			$a->create();
		} else if ($event->getName() == 'pull.create') {
			list($issue, $user,$owner, $repository) = $event->getArgs();
			$a = new Activity(Activity::getNextId(),'activity');
			$a->setImageUrl("https://www.gravatar.com/avatar/" . md5($user->getEmail()));
			$a->setDescription("{$user->getNickname()} sent <a href=\"/{$owner->getNickname()}/{$origin_repo->getName()}/issues/{$id}\">pull request #{$id}</a> on {$origin->getNickname()}/{$repository->getName()}");
			$a->setSenderId($user->getKey());
			$a->create();
		} else {
			inspect($event);
		}
	}
}