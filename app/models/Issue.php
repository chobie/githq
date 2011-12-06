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
	protected $labels = array();
	
	
	public function __construct($key)
	{
		parent::__construct($key);
		$this->registered_at = $_SERVER['REQUEST_TIME'];
	}
	
	public function hasLabel()
	{
		return (bool)(count($this->labels));
	}
	
	public function addLabel($label)
	{
		$this->labels[] = $label;
	}
	
	public function getLabels()
	{
		return $this->labels;
	}
	
	public function setOwner($owner)
	{
		$this->owner = $owner;
	}
	
	public function getOwner()
	{
		return $this->owner;
	}
	
	public function getStatus()
	{
		return $this->status;
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
	
	public function getRepository()
	{
		return $this->repository;
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
	
	public function getBodyAsMd()
	{
		$sd = new \Sundown($this->body);
		return $sd->to_html();
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
		$retVal = parent::create(function($stmt,$issue){
			$stmt->zAdd("issue_list.{$issue->getOwner()}.{$issue->getRepository()}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
			if ($issue->hasLabel()) {
				foreach ($issue->getLabels() as $offset => $label){
					$stmt->zAdd("issue_labels.{$issue->getOwner()}.{$issue->getRepository()}." . sha1($label) ,$issue->getRegisteredAtAsTimestamp(),$issue->getId());	
				}
			}
		});
		return $retVal;
	}
	
	public function closeIssue()
	{
		$this->status = self::CLOSED;
	}
	
	public function getRegisteredAt($format = 'Y-m-d H:i:s')
	{
		return date("Y-m-d H:i:s",$this->registered_at);
	}
	
	public function getRegisteredAtAsTimestamp()
	{
		return $this->registered_at;
	}
	
	public function getComments()
	{
		return $this->comments;
	}
	
	public function addComment($user_id, $comment)
	{
		$this->comments[] = new IssueComment($user_id,$comment);
	}
	
	public function save()
	{
		return parent::save(function ($stmt,$issue, $old){
			if ($old->getStatus() != $issue->getStatus()) {
				$stmt->zAdd("issue_list.{$issue->getOwner()}.{$issue->getRepository()}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
				$stmt->zRem("issue_list.{$issue->getOwner()}.{$issue->getRepository()}.{$old->getStatus()}",$issue->getId());
				$current_labels = $issue->getLabels();
				$old_labels = $old->getLabels();
				/*
				 * @todo array_diffだと両方の配列で含まれない物なので、追加されたか削除されたかが分からない
				if ($diff = array_diff($current_labels, $old_labels)) {
					foreach ($diff as $label) {
						$stmt->zAdd("issue_labels.{$issue->getOwner()}.{$issue->getRepository()}." . sha1($label) ,$issue->getRegisteredAtAsTimestamp(),$issue->getId());
					}
				}
				*/
			}
		});		
	}
}