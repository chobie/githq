<?php
class PublicKey
{
	const VALIDATION = "/^(?P<type>ecdsa-sha2-nistp256|ecdsa-sha2-nistp384|ecdsa-sha2-nistp521|ssh-dss|ssh-rsa)\s+(?P<key>[a-zA-Z0-9=\/+]+?)(?P<comment>\s.+)$/";
	protected $key;
	protected $title;
	
	public function __construct($string)
	{
		$this->key = trim($string);
	}
	
	/**
	 * set public key title
	 *
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * @return string title
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	/**
	 * verify ssh public key
	 * @return boolean 
	 */
	public function verify()
	{
		if(preg_match(self::VALIDATION,$this->key,$match)) {
			$this->key = join(' ',array($match['type'],$match['key'],$match['comment']));
			return true;
		} else {
			$this->key = null;
			return false;
		}
	}
	
	public function __toString()
	{
		return $this->key;
	}
}