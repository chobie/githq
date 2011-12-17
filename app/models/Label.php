<?php
class Label
{
	protected $id;
	protected $name;
	
	public function __construct()
	{
	}
	
	/**
	 * set label id
	 * 
	 * @param integer $id
	 */
	public function setId($id)
	{
		$this->id = $id;
	}
	
	/**
	 * get label id
	 * @return integer id
	 */
	public function getId()
	{
		return $this->id;
	}
	
	/**
	 * set label name
	 * 
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * get label name
	 * @return string name
	 */
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 * returns label name
	 * @return string name
	 */
	public function __toString()
	{
		return $this->name;
	}
}