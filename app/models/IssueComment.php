<?php
class IssueComment
{
	protected $user_id;
	protected $comment;
	protected $registered_at;

	public function __construct($user_id,$comment)
	{
		$this->user_id = $user_id;
		$this->comment = $comment;
		$this->registered_at = $_SERVER['REQUEST_TIME'];
	}
	
	public function getCommenter()
	{
		return User::get($this->user_id,'user');
	}

	public function getComment()
	{
		return $this->comment;
	}
	
	public function getRegisteredAt($format = 'Y-m-d H:i:s')
	{
		return date($format,$this->registered_at);
	}
}