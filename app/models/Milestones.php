<?php
class Milestones implements Iterator
{
	protected $position = 0;
	protected $sequence = 0;
	protected $milestones = array();
	
	public function __sleep()
	{
		return array('milestones','sequence');
	}
	

	public function __construct()
	{
	}
	
	public function getNextId()
	{
		$id = $this->sequence;
		$this->sequence++;
		return $id;
	}
	
	public function getMilestoneById($id)
	{
		if (isset($this->milestones[$id])) {
			return $this->milestones[$id];
		}
	}
	
	public function getMilestoneByName($name)
	{
		foreach ((array)$this->milestones as $milestone) {
			if ($milestone->getName() == $name) {
				return $milestone;
			}
		}
	}
	
	public function addMilestone(Milestone $milestone)
	{
		$this->milestones[$milestone->getId()] = $milestone;
	}
	
	public function current()
	{
		return $this->milestones[$this->position];
	}
	
	public function key()
	{
		return $this->position;
	}
	
	public function next()
	{
		++$this->position;
	}
	
	public function rewind()
	{
		$this->position = 0;
	}
	
	public function valid()
	{
		return isset($this->milestones[$this->position]);
	}
}