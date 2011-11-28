<?php
class User extends UIKit\Framework\UIStoredObject
{
	protected $email;
	protected $password;
	
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
		$this->passowrd = $password;
	}
	
	public function checkPassword($password)
	{
		return strcmp($this->password, $password);
	}
}