<?php
class User extends UIKit\Framework\UIStoredObject
{
	const SALT_LENGTH = 4;

	protected $email;
	protected $password;
	protected $salt;
	protected $repositories = array();
	
	public function getKey()
	{
		return $this->key;
	}
	
	public function type()
	{
		return 'user';
	}
	
	public function setEmail($email)
	{
		$this->email = $email;
	}
	
	public function setPassword($password)
	{
		$this->salt = $this->generateSalt();
		$this->passowrd = sha1(join(":",array($this->salt, $password)));
	}
	
	public function checkPassword($password)
	{
		return strcmp($this->password,sha1(join(":",array($this->salt, $password))));
	}
	
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
	
	public function addRepository(\Repository $repo)
	{
		if(!isset($this->repositories[$repo->getName()])) {
			$this->repositories[$repo->getName()] = $repo;
		}
	}
	
	public function getRepository($key)
	{
		if (isset($this->repositories[$key])) {
			return $this->repositories[$key];
		} else {
			return false;
		}
	}
	
	public function getRepositories()
	{
		return $this->repositories;
	}
	
	public function hasRepositories()
	{
		return (bool)$this->repositories;
	}
}