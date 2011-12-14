<?php
class User extends UIKit\Framework\UIStoredObject
{
	const SALT_LENGTH = 4;

	protected $profile;

	protected $email;
	protected $password;
	protected $salt;
	protected $repository_sequence = 0;
	protected $repositories = array();
	protected $public_keys = array();
	
	public function getNextRepositoryId()
	{
		return $this->repository_sequence++;
	}
	
	public function getEmail()
	{
		return $this->email;
	}
	
	public function getPublicKeys()
	{
		return $this->public_keys;
	}
	
	public function removePublicKey($offset)
	{
		if (isset($this->public_keys[$offset]))
			unset($this->public_keys[$offset]);
	}
	
	public function addPublicKey(PublicKey $key)
	{
		if(!in_array($key,$this->public_keys)){
			$this->public_keys[] = $key;
		}
	}
	
	public function getImageUrl()
	{
		return sprintf('http://www.gravatar.com/avatar/%s',md5($this->getEmail()));
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
	
	public function removeRepository($key)
	{
		if (isset($this->repositories[$key])) {
			$this->repositories[$key]->delete($this->getNickname());
			unset($this->repositories[$key]);
			return true;
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
	
	public function save()
	{
		$retVal = parent::save(function($stmt,$user,$old){
			$keys = $user->getPublicKeys();
			$old_keys = $old->getPublicKeys();
			if($diff = hash_diff($old_keys,$keys)) {
				if (isset($diff['-'])) {
					foreach($diff['-'] as $value) {
						$stmt->hdel("public_keys",$user->getKey() . "." . sha1($value->__toString()));
					}
				}
				
				if (isset($diff['+'])) {
					foreach($diff['+'] as $value) {
						$stmt->hset("public_keys",$user->getKey() . "." . sha1($value->__toString()), $value->__toString());
					}
				}
				$stmt->lpush("queue.public_keys",$user->getKey());
			}
		});
	}
}