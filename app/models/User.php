<?php
/**
 * githq's User blob 
 * 
 * @author chobie
 */
class User extends UIKit\Framework\UIStoredObject
{
	const KEY_USER_EMAIL           = 'pointer.user_id.email';
	const KEY_USER_NICKNAME        = 'pointer.user_id.nickname';
	const KEY_PUBLIC_KEYS          = 'public_keys';
	const KEY_PUBLIC_KEYS_QUEUE    = 'queue.public_keys';
	const SALT_LENGTH         = 4;
	
	const USER_TYPE_PUBLIC    = 0;
	const USER_TYPE_ORGANIZER = 1;
	const USER_TYPE_ADMIN     = 3;
	
	protected static $dissallowed_nicknames = array(
		"login","session","connect","about","organizations"
	);
	
	protected $profile;
	protected $nickname;
	protected $email;
	protected $password;
	protected $salt;
	protected $type = self::USER_TYPE_PUBLIC;
	protected $repository_sequence = 0;
	protected $repositories = array();
	protected $public_keys  = array();
	protected $members = array();
	
	public static function getIdByNickname($nickname)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		return $redis->get("pointer.user_id.nickname.{$nickname}");
	}
	
	public static function getIdByEmail($email)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$email = sha1($email);
		return $redis->get("pointer.user_id.email.{$email}");
	}
	
	
	public function getJoinedOrganizations()
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$members = $redis->smembers("user.{$this->getKey()}.organizations");
		$result = array();
		if ($members) {
			foreach ($members as $member) {
				$result[] = User::get($member,'user');
			}
		}
		return $result;
	}
	
	/**
	 * get members 
	 * @return array $members
	 */
	public function getMembers()
	{
		if (!$this->isOrganizer()) {
			return array();
		}
		return $this->members;
	}
	
	/**
	 * add member
	 * 
	 * @param string $user_id
	 * @throws \Exception
	 */
	public function addMember($user_id){
		if (!$this->isOrganizer()) {
			throw new \Exception("this user could not add member");
		}
		
		$key = array_search($user_id,$this->members);
		if ($key === false) {
			$this->members[] = $user_id;
		}
	}
	
	/**
	 * check user type as Organizer
	 * @return boolean 
	 */
	public function isOrganization()
	{
		return $this->type == self::USER_TYPE_ORGANIZER;
	}
	
	/**
	 * set user as organizer
	 */
	public function setUserAsOrganizer()
	{
		$this->type = self::USER_TYPE_ORGANIZER;
	}
	
	/**
	 * get next repository id.
	 * @return int $repository_sequence
	 */
	public function getNextRepositoryId()
	{
		return $this->repository_sequence++;
	}
		
	/**
	 * get user's email
	 * @return string $email
	 */
	public function getEmail()
	{
		return $this->email;
	}
	
	
	/**
	 * return public keys
	 * @return array public keys
	 */
	public function getPublicKeys()
	{
		return $this->public_keys;
	}
	
	
	/**
	 * remove specified public key with offset.
	 * 
	 * @param int $offset
	 */
	public function removePublicKey($offset)
	{
		if (isset($this->public_keys[$offset]))
			unset($this->public_keys[$offset]);
	}
	
	
	/**
	 * add public key
	 *
	 * @param PublicKey $key
	 */
	public function addPublicKey(PublicKey $key)
	{
		if(!in_array($key,$this->public_keys)){
			$this->public_keys[] = $key;
		}
	}
	
	public function getImageUrl()
	{
		return sprintf('https://secure.gravatar.com/avatar/%s',md5($this->getEmail()));
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
	
	public function getRepositoryById($id)
	{
		foreach($this->repositories as $repo) {
			if ($repo->getId() == $id) {
				return $repo;
			}
		}
	}
	
	/**
	 * remove specified repository.
	 * 
	 * this should not remove real repository.
	 * 
	 * @param string $key
	 */
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
	
	/**
	 * set user's nickname
	 * 
	 * @param string $nickname
	 */
	public function setNickname($nickname)
	{
		$this->nickname = $nickname;
	}
	
	/**
	 * get user nickname
	 * @return string $nickname
	 */
	public function getNickname()
	{
		return $this->nickname;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::create()
	 */
	public function create(\Closure $closure = null)
	{
		$nickname = $this->getNickname();
		if($nickname === null || $nickname === false) {
			throw new \InvalidArgumentException("user requires nickname");
		}

		return parent::create(function($stmt, $object) {
			$email = sha1($object->getEmail());
			$id = $object->getKey();
			$nickname = $object->getNickname();
			$stmt->set(User::KEY_USER_EMAIL . ".{$email}",$id);
			$stmt->set(User::KEY_USER_NICKNAME. ".{$nickname}",$id);
			if ($members = $object->getMembers()) {
				foreach($members as $member) {
					$stmt->sadd("organization.members.".$object->getKey(),$member);
					$stmt->sadd("user.".$member.".organizations",$object->getKey());
				}
			}
		});
	}

	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::save()
	 */
	public function save(\Closure $closure = null)
	{
		$retVal = parent::save(function($stmt,$user,$old){
			$keys = $user->getPublicKeys();
			$old_keys = $old->getPublicKeys();
			if($diff = hash_diff($old_keys,$keys)) {
				if (isset($diff['-'])) {
					foreach($diff['-'] as $value) {
						$stmt->hdel(User::KEY_PUBLIC_KEYS,$user->getKey() . "." . sha1($value->__toString()));
					}
				}
				
				if (isset($diff['+'])) {
					foreach($diff['+'] as $value) {
						$stmt->hset(User::KEY_PUBLIC_KEYS,$user->getKey() . "." . sha1($value->__toString()), $value->__toString());
					}
				}
				$stmt->lpush(User::KEY_PUBLIC_KEYS_QUEUE,$user->getKey());
			}
		});
	}

	/**
	 * get next user's id
	 * @return intger user_id
	 */
	public static function getNextId()
	{
		return $this->getClient()->incr("sequence.user_id");
	}
	
}