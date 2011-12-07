<?php
class Repository
{
	protected $name;
	protected $description;
	protected $homepage_url;
	protected $origin_user;
	
	public function setOrigin($origin_user)
	{
		$this->origin_user = $origin_user;
	}
	
	public function hasOrigin()
	{
		$retVal =false;
		if(!empty($this->origin_user)) {
			$retVal = true;
		}
		return $retVal;
	}
	
	public function getOrigin()
	{
		return $this->origin_user;
	}
	
	public function getOriginUser()
	{
		return User::get($this->origin_user, 'user');
	}
	
	/**
	 * create repository object
	 * 
	 * @param string $name repository name
	 */
	public function __construct($name)
	{
		$this->name = $name;
	}
	
	/**
	 * set repository description
	 * 
	 * @param string $desc
	 */
	public function setDescription($desc)
	{
		$this->description = $desc;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	/**
	 * set homepage url
	 * 
	 * @param string $url
	 */
	public function setHomepageUrl($url)
	{
		$this->homepage_url = $url;
	}
	
	public function getHomepageUrl()
	{
		return $this->homepage_url;
	}
	
	/**
	 * create current repository on filesystem
	 * 
	 * @param string $name user name
	 * @todo ちゃんと実装する
	 */
	public function create($name)
	{
		if (!is_dir("/home/git/repositories/{$name}/{$this->name}.git")) {
			system("mkdir -p /home/git/repositories/{$name}/{$this->name}.git");
			system("cd /home/git/repositories/{$name}/{$this->name}.git; git init --bare --shared");
			system("chmod 777 -R /home/git/repositories/{$name}/{$this->name}.git");
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * get current repository name
	 * 
	 * @return string $repository_name
	 */
	public function getName()
	{
		return $this->name;
	}
	
	public function fork(\User $owner, \User $forker)
	{
		$repository = new \Repository($this->getName());
		$repository->setDescription($this->getDescription());
		$repository->setHomepageUrl($this->getHomepageUrl());
		
		$repo_name = $repository->getName();
		$from_user = $owner->getNickname();
		$to_user = $forker->getNickname();

		if (!is_dir("/home/git/repositories/{$to_user}/{$repo_name}.git")) {
			$repository->setOrigin($owner->getKey());
			system("mkdir -p /home/git/repositories/{$to_user}/{$repo_name}.git");
			if(system("git clone file:///home/git/repositories/{$from_user}/{$repo_name}.git --bare --shared /home/git/repositories/{$to_user}/{$repo_name}.git")){
				return $repository;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}