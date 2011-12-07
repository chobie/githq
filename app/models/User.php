<?php
class User extends UIKit\Framework\UIStoredObject
{
	const SALT_LENGTH = 4;

	protected $profile;

	protected $email;
	protected $password;
	protected $salt;
	protected $repositories = array();	
	
	public function getImageUrl()
	{
		return sprintf('http://graph.facebook.com/%s/picture',$this->getKey());
	}
	
	/**
	 * return current blob key.
	 * 
	 * @return string $key
	 */
	public function getKey()
	{
		return $this->key;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::type()
	 */
	public function type()
	{
		return 'user';
	}
	
	/**
	 * set primary email
	 * 
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}
	
	/**
	 * set new password
	 * 
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->salt = $this->generateSalt();
		$this->passowrd = sha1(join(":",array($this->salt, $password)));
	}
	
	/**
	 * check current password and recived password same.
	 *
	 * @param string $password
	 * @return bool 
	 */
	public function checkPassword($password)
	{
		return strcmp($this->password,sha1(join(":",array($this->salt, $password))));
	}
	
	/**
	 * generate salt for password
	 * 
	 * @return string salt
	 */
	private function generateSalt()
	{
		mt_srand();
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789~!@#$%^&*(()_+?';
		$length = strlen($chars);
		$salt = "";
		for ($i = 0; $i < self::SALT_LENGTH; $i++) {
			$key = mt_rand(0,$length-1);
			$salt .= $chars[$key];
		}
		
		return $salt;
	}
	
	/**
	 * add \Repository object to current blob
	 * 
	 * @param \Repository $repo
	 * @return void
	 */
	public function addRepository(\Repository $repo)
	{
		if(!isset($this->repositories[$repo->getName()])) {
			$this->repositories[$repo->getName()] = $repo;
		}
	}
	
	/**
	 * get specified repository
	 * 
	 * @param string $key repository name
	 * @return \Repository
	 */
	public function getRepository($key)
	{
		if (isset($this->repositories[$key])) {
			return $this->repositories[$key];
		} else {
			return false;
		}
	}
	
	/**
	 * get all repositories
	 * @return array $repositories
	 */
	public function getRepositories()
	{
		return $this->repositories;
	}
	
	/**
	 * check has user any repos
	 * @return bool
	 */
	public function hasRepositories()
	{
		return (bool)$this->repositories;
	}
	
	/**
	 * get current user profile
	 * @return Profile $profile
	 */
	public function getProfile()
	{
		if (!isset($this->profile)) {
			$this->profile = new Profile();
		}
		return $this->profile;
	}
	
	public function setNickname($nickname)
	{
		$this->nickname = $nickname;
	}
	
	public function getNickname()
	{
		return $this->nickname;
	}
	
	public function create()
	{
		$retVal = false;
		if ($retVal = parent::create()) {
			UserPointer::setIdWithEmail($this->key, $this->email);
			UserPointer::setIdWithNickname($this->key, $this->nickname);
		}
		return $retVal;
	}
}