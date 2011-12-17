<?php
class Labels implements Iterator
{
	protected $position = 0;
	protected $labels = array();
	
	public function __sleep()
	{
		return array('labels');
	}
	

	public function __construct()
	{
	}
	
	/**
	 * get next label id
	 * @toto この実装だとだめ
	 * @return integer id
	 */
	public function getNextId()
	{
		return count($this->labels);
	}
	
	/**
	 * get label by id
	 * 
	 * @param Label $label
	 */
	public function getLabelById($id)
	{
		if (isset($this->labels[$id])) {
			return $this->labels[$id];
		}
	}
	
	/**
	 * get label by name
	 * 
	 * @param string $name
	 * @return Label $label
	 */
	public function getLabelByName($name)
	{
		foreach ((array)$this->labels as $label) {
			if ($label->getName() == $name) {
				return $label;
			}
		}
	}
	
	/**
	 * add label
	 * 
	 * @param Label $label
	 * @return Label
	 */
	public function addLabel(Label $label)
	{
		$this->labels[$label->getId()] = $label;
	}
	
	
	public function current()
	{
		return $this->labels[$this->position];
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
		return isset($this->labels[$this->position]);
	}
}