<?php
require_once __DIR__ . "/../vendor/php-uikit/UIKit/Framework/UIAutoLoader.php";
require_once __DIR__ . '/../vendor/twig/lib/Twig/Autoloader.php';
require_once __DIR__ . '/../vendor/Albino/src/Albino.php';
require_once __DIR__ . '/../vendor/php-sdk/src/facebook.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Line.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Lines.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Parser.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Struct.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/File.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Hunk.php';

UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/controllers');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/models');
UIKit\Framework\UIAutoLoader::register();
Twig_Autoloader::register();

date_default_timezone_set('Asia/Tokyo');

require __DIR__ . "/../config/development.php";

class GitHQApplicationDelegate extends UIKit\Framework\UIWebApplicationDelegate
{
	protected function getRouting()
	{
		$router = new UIKit\Framework\UIRouter();
		$router->add('/{user}/{repository}/issue_comments',array(
												'controller' => 'IssuesController',
												'action' => 'onIssueComments',
		));
		$router->add('/{user}/{repository}/issues',array(
									'controller' => 'IssuesController',
									'action' => 'onDefault',
		));
		$router->add('/{user}/{repository}/issues/new',array(
									'controller' => 'IssuesController',
									'action' => 'onNew',
		));
		$router->add('/{user}/{repository}/issues/{id,digit}',array(
												'controller' => 'IssuesController',
												'action' => 'onIssue',
		));
		
		$router->add('/{user}/{repository}/commits/{refs}',array(
						'controller' => 'RootController',
						'action' => 'onCommits',
		));
		$router->add('/{user}/{repository}/commit/{commit}',array(
									'controller' => 'RootController',
									'action' => 'onCommit',
		));
		
		$router->add('/{user}/{repository}/blob/{refs}/{path,greed}',array(
						'controller' => 'RootController',
						'action' => 'onBlob',
		));
		$router->add('/{user}/{repository}/tree/{refs}/{path,greed}',array(
						'controller' => 'RootController',
						'action' => 'onTree',
		));
		$router->add('/{user}/{repository}.git/{path,greed}',array(
						'controller' => 'RootController',
						'action' => 'onDefault',
		));
		$router->add('/{action,alpha,camel,prepend(on)}',array(
						'controller' => 'RootController',
						'action' => 'default',
		));
		$router->add('/{controller,vars,camel,append(Controller)}/{action,vars,camel,prepend(on),optional}',array(
						'controller' => 'RootController',
						'action' => 'default',
		));
		return $router;
	}
	
	public function didFinishLaunchingWithOptions($app, $options = array())
	{
		$dispatcher = new UIKit\Framework\UIUrlDispatcher($this->getRouting());
		$result = $dispatcher->dispatch();
		$this->controller = $result['controller'];
		$this->action = $result['action'];
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