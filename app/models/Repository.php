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
	protected $features = array(
		"issue" => true,
		"pull" => true,
		"fork" => true,
	);
	
	
	public function watch(User $owner,$watcher)
	{
		$redis = \GitHQ\Bundle\AbstractController::getRedisClient();
		$redis->sadd(sprintf("watch.%s.%s",$owner->getKey(), $this->getId()), $watcher->getKey());
	}
	
	public static function getWatchedCount(User $user, Repository $repository)
	{
		$redis = \GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->scard(sprintf("watch.%s.%s",$user->getKey(), $repository->getId()));
	}
	
	public function isIssueEnabled()
	{
		return $this->features['issue'];
	}
	
	public function enableIssue()
	{
		$this->features['issue'] = true;
	}
	
	public function disableIssue()
	{
		$this->features['issue'] = false;
	}
	
	
	public function enablePull()
	{
		$this->features['pull'] = true;
	}
	
	public function disablePull()
	{
		$this->features['pull'] = false;
	}
	
	public function enableFork()
	{
		$this->features['fork'] = true;
	}
	
	public function disableFork()
	{
		$this->features['fork'] = false;
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function isPublic()
	{
		return self::TYPE_PUBLIC == $this->type;
	}
	
	public function isPrivate()
	{
		return self::TYPE_PRIVATE == $this->type;
	}
	
	public function setStatus($status)
	{
		$this->type = $status;
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
	
	public function hasWritePermission(User $owner, $user)
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
		$id = $this->getId();
		if (is_dir("/home/git/repositories/{$name}/{$id}")) {
			system("rm -rf /home/git/repositories/{$name}/{$id}");
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
		$repo_name = $forker->getNextRepositoryId();
		$repository->setId($repo_name);
		$repository->setDescription($this->getDescription());
		$repository->setHomepageUrl($this->getHomepageUrl());
		
		$from_user = $owner->getKey();
		$to_user = $forker->getKey();
		
		if (!is_dir("/home/git/repositories/{$to_user}/{$repo_name}")) {
			$repository->setOrigin($owner->getKey());
			system("mkdir -p /home/git/repositories/{$to_user}/{$repo_name}");
			if(system("git clone file:///home/git/repositories/{$from_user}/{$this->getId()} --bare --shared /home/git/repositories/{$to_user}/{$repo_name}")){
				return $repository;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
}