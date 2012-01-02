<?php
class Issue extends \UIKit\Framework\ObjectStore
{
	const OPENED = 0;
	const CLOSED = 1;
	
	const TYPE_ISSUE = 0;
	const TYPE_PULL = 1;
	
	protected $sequence;
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
	protected $milestone;
	protected $ref = array();
	protected $assigned;
	
	public function getAssigneeId()
	{
		return $this->assigned;
	}
	
	public function getAssignee()
	{
		return User::get($this->assigned);
	}
	
	public function setAssignee($user_id)
	{
		$this->assigned = $user_id;
	}
	
	public function isAssigned()
	{
		if (!empty($this->assigned)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * get current issue key
	 * @return string $key
	 */
	public function getKey()
	{
		return $this->key;
	}
	
	/**
	 * create issue blob
	 * 
	 * @param string $key
	 */
	public function __construct($key)
	{
		parent::__construct($key);
		$this->registered_at = $_SERVER['REQUEST_TIME'];
	}
	
	/**
	 * set issue milestone
	 *
	 * @param int $milestone
	 */
	public function setMilestoneId($milestone)
	{
		$this->milestone = $milestone;
	}
	
	/**
	 * get issue milestone
	 * @return string $milestone
	 */
	public function getMilestoneId()
	{
		return $this->milestone;
	}
	
	/**
	 * check issue has milestone
	 * @return boolean
	 */
	public function hasMilestone()
	{
		if (isset($this->milestone)) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * remove current milestone
	 */
	public function removeMilestone()
	{
		$this->milestone = null;
	}
	
	/**
	 * check issue has labels
	 * @return boolean 
	 */
	public function hasLabel()
	{
		return (bool)(count($this->labels));
	}
	
	/**
	 * add label to this blob
	 * 
	 * @param string $label_id
	 */
	public function addLabelId($label_id)
	{
		$key = array_search($label_id,$this->labels);
		if ($key === false) {
			$this->labels[] = $label_id;
		} else {
			return false;
		}
		
	}
	
	/**
	 * remove specified label id
	 * 
	 * @param id $label_id
	 */
	public function removeLabelId($label_id)
	{
		$key = array_search($label_id,$this->labels);
		if ($key !== false) {
			unset($this->labels[$key]);
		}
	}
	
	/**
	 * get label ids
	 * @return Labels
	 */
	public function getLabelIds()
	{
		return $this->labels;
	}
	
	/**
	 * set issue owner.
	 * 
	 * @param string $owner_id
	 */
	public function setOwner($owner)
	{
		$this->owner = $owner;
	}
	
	/**
	 * get owner id.
	 * @return string owner
	 */
	public function getOwner()
	{
		return $this->owner;
	}
	
	/**
	 * get current issue status.
	 * @return int status
	 */
	public function getStatus()
	{
		return $this->status;
	}
	
	/**
	 * does issue close?
	 * @return boolean
	 */
	public function isClosed()
	{
		return ($this->status == self::CLOSED) ? true : false;
	}
	
	/**
	 * does issue open?
	 * @return boolean
	 */
	public function isOpened()
	{
		return ($this->status == self::OPENED) ? true : false;
	}
	
	/**
	 * set repository id
	 * 
	 * @param string $repository_id
	 */
	public function setRepositoryId($repository)
	{
		$this->repository = $repository;
	}
	
	/**
	 * get repository id
	 * @return string repository id
	 */
	public function getRepositoryId()
	{
		return $this->repository;
	}
	
	/**
	 * get current issue id
	 * @return string id
	 */
	public function getId()
	{
		return $this->sequence;
	}
	
	/**
	 * set current issue id
	 * 
	 * @param string $seq
	 */
	public function setId($seq)
	{
		$this->sequence = $seq;
	}
	
	/**
	 * set author id
	 * 
	 * @param string $author_id
	 */
	public function setAuthor($author_id)
	{
		$this->author_id = $author_id;
	}
	
	/**
	 * get author user object.
	 * @return User
	 */
	public function getAuthor()
	{
		return User::get($this->author_id,'user');
	}
	
	/**
	 * set issue title
	 * 
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
	
	/**
	 * get issue title
	 * @return string title
	 */
	public function getTitle()
	{
		return $this->title;
	}
	
	/**
	 * set issue body
	 * 
	 * @param string $body
	 */
	public function setBody($body)
	{
		$this->body = $body;
	}
	
	/**
	 * get issue body
	 * @return string body
	 */
	public function getBody()
	{
		return $this->body;
	}
	
	/**
	 * get body string as Markdown
	 * @return string markdown nized string
	 */
	public function getBodyAsMd()
	{
		$sd = new \Sundown($this->body);
		return $sd->to_html();
	}
	
	/**
	 * set issue status
	 * 
	 * @param int $status
	 */
	public function setStatus($status)
	{
		$this->status = $status;
	}
	
	public function setPullrequest()
	{
		$this->type = self::TYPE_PULL;
	}
	
	public function isPullrequest()
	{
		return $this->type == self::TYPE_PULL;
	}
	
	public function attachRef($owner_id, $branch_name,$repository_id)
	{
		$this->ref = array(
			'owner' => $owner_id,
			'branch' => $branch_name,
			'repository' => $repository_id,
		);
	}
	
	public function getRef()
	{
		return $this->ref;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::type()
	 */
	public function type()
	{
		return 'issue';
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::create()
	 */
	public function create(\Closure $closure = null)
	{
		$retVal = false;
		$retVal = parent::create(function($stmt,$issue){
			$stmt->zAdd("issue_list.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
			if ($issue->hasLabel()) {
				foreach ($issue->getLabels() as $offset => $label){
					$stmt->zAdd("issue_labels.{$issue->getOwner()}.{$issue->getRepositoryId()}." . sha1($label) ,$issue->getRegisteredAtAsTimestamp(),$issue->getId());	
				}
			}
			if ($issue->isPullrequest()){
				$stmt->zAdd("pull_list.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
			}
		});
		return $retVal;
	}
	
	/**
	 * set issuse status as open
	 */
	public function openIssue()
	{
		$this->status = self::OPENED;
	}
	
	/**
	 * set issue status as close
	 */
	public function closeIssue()
	{
		$this->status = self::CLOSED;
	}
	
	/**
	 * get regisered time
	 * 
	 * @param string $format
	 */
	public function getRegisteredAt($format = 'Y-m-d H:i:s')
	{
		return date("Y-m-d H:i:s",$this->registered_at);
	}
	
	/**
	 * get registered at
	 * @return int
	 */
	public function getRegisteredAtAsTimestamp()
	{
		return $this->registered_at;
	}
	
	/**
	 * get comments
	 * @return array $comment
	 */
	public function getComments()
	{
		return $this->comments;
	}
	
	/**
	 * add comment
	 * 
	 * @param string $user_id
	 * @param string $comment
	 */
	public function addComment($user_id, $comment)
	{
		$this->comments[] = new IssueComment($user_id,$comment);
	}
	
	/**
	 * (non-PHPdoc)
	 * @see UIKit\Framework.UIStoredObject::save()
	 */
	public function save(\Closure $closure = null)
	{
		return parent::save(function ($stmt,$issue, $old){
			$current_labels = $issue->getLabelIds();
			$old_labels = $old->getLabelIds();
				
			if ($old->getStatus() != $issue->getStatus()) {
				$stmt->zAdd("issue_list.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
				$stmt->zAdd("pull_list.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
				foreach($issue->getLabelIds() as $label_id) {
					$stmt->zAdd("issue_labels.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$label_id}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());			
				}
				$stmt->zDelete("issue_list.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$old->getStatus()}",$issue->getId());
				$stmt->zDelete("pull_list.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$old->getStatus()}",$issue->getId());
				foreach($old->getLabelIds() as $label_id) {
					$stmt->zDelete("issue_labels.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$label_id}.{$issue->getStatus()}",$issue->getId());
				}
				
			}

			if ($diff = hash_diff($old_labels,$current_labels)) {
				if (isset($diff['-'])){
					foreach ($diff['-'] as $label_id) {
						//@todo issue_list.<owner>.repository.label.status,
						$stmt->zDelete("issue_labels.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$label_id}.{$issue->getStatus()}",$issue->getId());
					}
				}

				if (isset($diff['+'])){
					foreach ($diff['+'] as $label_id) {
						//@todo issue_list.<owner>.repository.label.status,  
						$stmt->zAdd("issue_labels.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$label_id}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(),$issue->getId());
					}
				}
			}
						
			if ($old->getMilestoneId() !== $issue->getMilestoneId()) {
				$mile = $old->getMilestoneId();
				if($mile !== false){
					$stmt->zDelete("issue_milestone.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$mile}.{$issue->getStatus()}",$issue->getId());
				}
				$mile = $issue->getMilestoneId();
				if($mile !== false){
					$stmt->zAdd("issue_milestone.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$mile}.{$issue->getStatus()}",$issue->getRegisteredAtAsTimestamp(), $issue->getId());
				}
			}

			if ($old->getAssigneeId() !== $issue->getAssigneeId()) {
				$id = $old->getAssigneeId();
				if($id !== false){
					$stmt->zDelete("issue_assigned.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$id}.{$old->getStatus()}",$issue->getId());
				}
				$id = $issue->getAssigneeId();
				if($id !== false){
					$stmt->zAdd("issue_assigned.{$issue->getOwner()}.{$issue->getRepositoryId()}.{$id}.{$issue->getStatus()}", $issue->getRegisteredAtAsTimestamp(), $issue->getId());
 				}
			}
		});		
	}
	
	public function vote(User $user)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$redis->sadd("issue_votes.{$this->getOwner()}.{$this->getRepositoryId()}.{$this->getId()}",$user->getKey());
	}
	
	public function unvote(User $user)
	{
		$redis = GitHQ\Bundle\AbstractController::getRedisClient();
		$redis->srem("issue_votes.{$this->getOwner()}.{$this->getRepositoryId()}.{$this->getId()}",$user->getKey());
	}
	
}