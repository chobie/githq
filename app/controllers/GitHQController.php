<?php
namespace GitHQ\Bundle;

abstract class AbstractController extends \UIKit\Framework\HTTPFoundation\Controller\ApplicationController
{
	protected static $redis;

	protected function getDefaultView()
	{
		$view = new $this->view($this->container);
		$view->setUser($this->getUser());
		return $view;
	}
	
	public function generateUrl($id, $params = array())
	{
		static $xml;
		if (!$xml) {
			$xml = simplexml_load_string(file_get_contents("/home/chobie/githq.org/app/config/routes.xml"));
		}
		
		if ($element = $xml->xpath("//route[@id='{$id}']")) {
			///{user,vars}
			$path = $element[0]->attributes()->pattern;
			$offset = 0;
			if(preg_match_all('/{[a-zA-Z0-9_,)(-]+?}/',$path,$matches,PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE)) {
				foreach($matches[0] as $item) {
					preg_match("/\{(?P<name>[a-zA-Z0-9_]+)/",$item[0],$m);
					$p = $options[$m['name']];
					$path = substr_replace($path,$p,$item[1]+$offset,strlen($item[0]));
					$offset += ($item[1]+strlen($p)) - ($item[1]+strlen($item[0]));
				}
			}
		} else {
			throw new \Exception("specified routing does not find");
		}
		
		return $path;
	}
	
	public function render($template, $args = array())
	{
		if (!isset($args['user'])) {
			$args['user'] = $this->getUser();
		}

		return parent::render($template, $args);
	}

	/**
	* resolve file name of inside git repository.
	*
	* @param \Git\Tree $tree
	* @param string $name filename or path
	* @return \Git\Object $object
	*/
	protected function resolve_filename($tree,$name)
	{
		$list = explode("/",$name);
		$cnt = count($list);
	
		$i = 1;
		while ($fname = array_shift($list)) {
			foreach ($tree->getIterator() as $entry) {
				if ($entry->name == $fname) {
					if ($i < $cnt && $entry->isTree()) {
						return $this->resolve_filename($entry->toObject(),join("/",$list));
					} else {
						return $entry->toObject();
					}
				}
			}
			$i++;
		}
	
		return null;
	}
	
	
	public function __construct($container)
	{
		parent::__construct($container);
	}
	
	/**
	 * @deprecated
	 */
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

	public function on404()
	{
		$this->render("404.htm");
	}
}