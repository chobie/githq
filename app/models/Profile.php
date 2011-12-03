<?php
class Profile
{
	protected $name;
	protected $email;
	protected $homepage;
	protected $company;
	protected $location;
	
	public function __construct()
	{
	}
	
	/**
	 * set user name
	 *
	 * this property targets to show public profile.
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * get user name
	 * @return string $name
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * set email address for public profile
	 * 
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}
	
	/**
	 * get email address
	 * @return string $email
	 */
	public function getEmail()
	{
		return $this->email;
	}
	
	/**
	 * set homepage url
	 * 
	 * @param string $homepage
	 */
	public function setHomepage($homepage)
	{
		$this->homepage = $homepage;
	}
	
	/**
	 * get homepage url
	 * 
	 * @param string $homepage_url
	 */
	public function getHomepage()
	{
		return $this->homepage;
	}
	
	/**
	 * set comany name
	 * 
	 * @param string $company
	 */
	public function setCompany($company)
	{
		$this->company = $company;
	}
	
	/**
	 * get company name
	 * @return string $company name
	 */
	public function getCompany()
	{
		return $this->company;
	}
	
	/**
	 * set user location
	 * 
	 * @param string $location
	 */
	public function setLocation($location)
	{
		$this->location = $location;
	}
	
	/**
	 * get user location
	 * @return string 
	 */
	public function getLocation()
	{
		return $this->location;
	}
		
}