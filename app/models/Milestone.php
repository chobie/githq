<?php
class Milestone
{
	protected $id;
	protected $name;
	
	public function __construct()
	{
	}
	
	/**
	 * set milestone id
	 * 
	 * @param string $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}
	
	/**
	 * get current milestone id
	 * @return string id
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * set milestone name
	 * 
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * get milestone name
	 * @return string $name
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * get current milestone name
	 */
	public function __toString()
	{
		return (string)$this->name;
	}
}