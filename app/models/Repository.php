<?php
class Repository
{
	protected $name;
	protected $description;
	protected $homepage_url;
	
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	public function setDescription($desc)
	{
		$this->description = $desc;
	}
	
	public function setHomepageUrl($url)
	{
		$this->homepage_url = $url;
	}
	
	public function create()
	{
		if (!is_dir("/tmp/{$this->name}")) {
			system("mkdir /tmp/{$this->name}");
			system("cd /tmp/{$this->name}; git init");
			return true;
		} else {
			return false;
		}
	}
	
	public function getName()
	{
		return $this->name;
	}
}