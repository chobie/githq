<?php
class Label
{
	protected $id;
	protected $name;
	
	public function __construct()
	{
	}
	
	public function setId($id)
	{
		$this->id = $id;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setName($name)
	{
		$this->name = $name;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	public function __toString()
	{
		return $this->name;
	}
}