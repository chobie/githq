<?php
class Repository
{
	const TYPE_PUBLIC = 0x0;
	const TYPE_PRIVATE = 0x01;
	
	protected $id;
	protected $name;
	protected $description;
	protected $homepage_url;
	protected $origin_user;
	protected $labels = array();
	protected $milestones;
	protected $type = self::TYPE_PUBLIC;
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setPublic()
	{
		$this->type = self::TYPE_PUBLIC;
	}
	
	public function setPrivate()
	{
		$this->type = self::TYPE_PRIVATE;
	}
	
	public function hasPermission(User $owner, $user)
	{
		if ($this->type == self::TYPE_PUBLIC) {
			return true;
		}
		
		if (!$user instanceof User) {
			return false;
		}
		
		if ($this->type == self::TYPE_PRIVATE) {
			if ($owner->getKey() == $user->getKey()) {
				return true;
			} else {
				return false;
			}
		} else {
			return true;
		}
	}
	
	public function getMilestones()
	{
		return $this->milestones;
	}
	
	public function getLabels()
	{
		return $this->labels;
	}
	
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
		$this->labels = new Labels();
		$this->milestones = new Milestones();
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
		$id = $this->getId();
		if (!is_dir("/home/git/repositories/{$name}/{$id}")) {
			system("mkdir -p /home/git/repositories/{$name}/{$id}");
			system("cd /home/git/repositories/{$name}/{$id}; git init --bare --shared");
			system("chmod 777 -R /home/git/repositories/{$name}/{$id}");
			return true;
		} else {
			return false;
		}
	}
	
	public function delete($name)
	{
		if (is_dir("/home/git/repositories/{$name}/{$this->name}.git")) {
			system("rm -rf /home/git/repositories/{$name}/{$this->name}.git");
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