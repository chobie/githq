<?php
require __DIR__ . '/../config/env.php';

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
		$router->add('/{user}/{repository}/issues/update',array(
											'controller' => 'IssuesController',
											'action' => 'onUpdate',
		));
		$router->add('/{user}/{repository}/issues/new',array(
									'controller' => 'IssuesController',
									'action' => 'onNew',
		));
		$router->add('/{user}/{repository}/issues/edit/{id,digit}',array(
														'controller' => 'IssuesController',
														'action' => 'onEdit',
		));
		$router->add('/{user}/{repository}/issues/{id,digit}',array(
												'controller' => 'IssuesController',
												'action' => 'onIssue',
		));
		
		$router->add('/{user}/{repository}/commits/{refs}/{path,greed}',array(
								'controller' => 'RootController',
								'action' => 'onCommitsHisotry',
		));
		$router->add('/{user}/{repository}/commits/{refs}',array(
						'controller' => 'RootController',
						'action' => 'onCommits',
		));
		
		$router->add('/{user}/{repository}/commit/{commit}',array(
									'controller' => 'RootController',
									'action' => 'onCommit',
		));
		$router->add('/{user}/{repository}/admin',array(
								'controller' => 'AdminController',
								'action' => 'onDefault',
		));
		$router->add('/{user}/{repository}/admin/delete',array(
										'controller' => 'AdminController',
										'action' => 'onDelete',
		));
		$router->add('/{user}/{repository}/admin/update',array(
												'controller' => 'AdminController',
												'action' => 'onUpdate',
		));
		
		$router->add('/{user}/{repository}/blame/{refs}/{path,greed}',array(
								'controller' => 'RootController',
								'action' => 'onBlame',
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
		$router->add('/about',array(
					'controller' => 'RootController',
					'action' => 'onAbout'
		));
		$router->add('/{action,alpha,camel,prepend(on)}',array(
						'controller' => 'RootController',
						'action' => 'default',
		));
		/*
		$router->add('/{user,vars}/',array(
								'controller' => 'RootController',
								'action' => 'onUser',
		));
		*/
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