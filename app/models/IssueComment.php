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
	
	/**
	 * get commenter user object
	 * @return User $user
	 */
	public function getCommenter()
	{
		return User::get($this->user_id,'user');
	}

	/**
	 * get comment 
	 * @return string
	 */
	public function getComment()
	{
		return $this->comment;
	}
	
	/**
	 * get comment as Markdown
	 * @return string $string
	 */
	public function getCommentAsMd()
	{
		$sd = new \Sundown($this->comment);
		return $sd->to_html();
	}
	
	/**
	 * get registered time
	 * 
	 * @param string $format
	 */
	public function getRegisteredAt($format = 'Y-m-d H:i:s')
	{
		return date($format,$this->registered_at);
	}
}