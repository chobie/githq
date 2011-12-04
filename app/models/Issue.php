<?php
class Issue extends \UIKit\Framework\UIStoredObject
{
	const OPENED = 0;
	const CLOSED = 1;
	
	const TYPE_ISSUE = 0;
	const TYPE_PULL = 1;
	
	protected $author_id;
	protected $title;
	protected $type = self::TYPE_ISSUE;
	protected $body;
	protected $status = self::OPENED;
	protected $owner;
	protected $repository;
	protected $registered_at;
	protected $comments = array();
	
	public function __construct($key)
	{
		parent::__construct($key);
		$this->registered_at = $_SERVER['REQUEST_TIME'];
	}
	
	public function setOwner($owner)
	{
		$this->owner = $owner;
	}
	
	public function isClosed()
	{
		return ($this->status == self::CLOSED) ? true : false;
	}
	
	public function isOpened()
	{
		return ($this->status == self::OPENED) ? true : false;
	}
	
	public function setRepository($repository)
	{
		$this->repository = $repository;
	}
	
	public function getId()
	{
		return $this->key;
	}
	
	public function setAuthor($author_id)
	{
		$this->author_id = $author_id;
	}
	
	public function getAuthor()
	{
		return User::get($this->author_id,'user');
	}
	
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	public function getTitle()
	{
		return $this->title;
	}
	
	public function setBody($body)
	{
		$this->body = $body;
	}
	
	public function getBody()
	{
		return $this->body;
	}
	
	public function setStatus($status)
	{
		$this->status = $status;
	}
	
	public function type()
	{
		return 'issue';
	}
	
	public function create()
	{
		$retVal = false;
		if ($retVal = parent::create()) {
			IssueReferences::addSubmitedList($this->owner,$this->repository,$this->key,$this->status,$this->registered_at);
			$retVal = true;
		}
		return $retVal;
	}
	
	public function getRegisteredAt($format = 'Y-m-d H:i:s')
	{
		return date("Y-m-d H:i:s",$this->registered_at);
	}
	
	public function getComments()
	{
		return $this->comments;
	}
	
	public function addComment($user_id, $comment)
	{
		$this->comments[] = new IssueComment($user_id,$comment);
	}
}