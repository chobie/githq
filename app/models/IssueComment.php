<?php
class IssueComment
{
	protected $user_id;
	protected $comment;
	protected $registered_at;
	protected $type = 0;
	protected $vote = array();
	protected $opaque;

	public function __construct($user_id,$comment, $opaque = null)
	{
		$this->user_id = $user_id;
		$this->comment = $comment;
		$this->registered_at = $_SERVER['REQUEST_TIME'];
		$this->opaque = $opaque;
	}
	
	public function getVoteCount()
	{
		return count($this->vote);
	}
	
	public function vote(User $user)
	{
		if (array_search($user->getKey(), $this->vote) === false) {
			$this->vote[] = $user->getKey();
		}
	}
	
	public function unvote(User $user)
	{
		if (($offset = array_search($user->getKey(), $this->vote)) !== false) {
			unset($this->vote[$offset]);
		}
	}
	
	public function getOpaque()
	{
		return $this->opaque;
	}
	
	public function isComment()
	{
		return $this->type == 0;
	}
	
	public function isReferenceEvent()
	{
		return $this->type == 3;
	}
	
	public function isEvent()
	{
		return $this->type > 0;
	}

	public function setTypeAsReopenEvent()
	{
		$this->type = 1;
	}

	public function setTypeAsCloseEvent()
	{
		$this->type = 2;
	}

	public function setTypeAsReferenceEvent()
	{
		$this->type = 3;
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
	
	public function setComment($comment)
	{
		$this->comment = $comment;
	}
	
	/**
	 * get comment as Markdown
	 * @return string $string
	 */
	public function getCommentAsMd()
	{
		$sd = new \Sundown($this->comment);
		$data = $sd->to_html();
		$data = preg_replace("/\s@([a-zA-Z][a-zA-Z0-9_-]+)/"," <a href=\"/$1\">@$1</a>",$data);
		return $data;
		
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