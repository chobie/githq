<?php
class UserTest extends Mock_TestCase
{
	/**
	* testing create
	*/
	public function testCreate()
	{
		$user= new User('1','user');
		$user->setNickname("nickname"); 
		$res = $user->create();

		foreach ($res as $result) {
			$this->assertEquals($result,true,"user object does not save correctly");
		}
		
		$this->assertEquals($user->getNickname(),"nickname","nickname does not same");
	}
	
	public function testSetUserAsOrganizer()
	{
		$user= new User('1','user');
		$user->setNickname("nickname");
		$user->setUserAsOrganizer();
		$user->addMember(2);
		
		$members = $user->getMembers();
		foreach ($members as $member) {
			$this->assertEquals($member,"2","member could not add correctly");
		}
	}
}