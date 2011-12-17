<?php
class ActivityTest extends Mock_Testcase
{		
	/**
	 * testing create
	 */
	public function testCreate()
	{
		$activity = new Activity('1','activity');
		$activity->setSenderId(1);
		
		$rets = $activity->create();
		
		$max = count($rets) - 3;
		$this->assertEquals($rets[$max],true,"could not save activity");
	}
	
	/**
	 * test set | get description
	 * 
	 */
	public function testSetGetDescription()
	{
		$activity = new Activity('2','activity');
		$activity->setDescription("Hello World");
		$activity->setSenderId(1);
		
		$this->assertEquals($activity->getDescription(), "Hello World","could not get|setDescription");
		$activity->create();
		$this->assertEquals($activity->getDescription(), "Hello World","could not get description");
	}
}