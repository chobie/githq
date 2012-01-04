<?php 
abstract class BaseView
{
	protected $use_layout = true;
	protected $template;
	protected $container;
	protected $user;
	
	public function __construct($container)
	{
		$this->container = $container;
		$this->render = new UIKit\Framework\Render\Twig($container);
	
		$this->viewDidLoad();
	}
	
	public function setUser($user)
	{
		$this->user = $user;
	}
	
	public function viewDidLoad()
	{
		if (!$this->template) {
			$this->template = "index.htm";
		}
	}
	
	public function setTemplate($file)
	{
		$this->template = $file;
		return $this;
	}
	
	public function prepareResponse($vars = array())
	{
		return new UIKit\Framework\HTTPFoundation\Response\HTTPResponse($this->render->render2($this->template, $vars));
	}
} 