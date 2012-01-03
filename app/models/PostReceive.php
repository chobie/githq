<?php
class PostReceive
{
	protected $owner_id;
	protected $repository_id;
	protected $committer_id;
	protected $oldrev;
	protected $newrev;
	protected $refname;
	
	public function __construct($owner_id, $repository_id, $committer_id, $oldrev,$newrev,$refname)
	{
		$this->owner_id = $owner_id;
		$this->repository_id = $repository_id;
		$this->committer_id = $committer_id;
		$this->oldrev = $oldrev;
		$this->newrev = $newrev;
		$this->refname = $refname;
	}
	
	public function getPushType()
	{
		//create,update,delete
	}
	
	public function getBranchType()
	{
		//branch,tags,remotes
	}
	
	public function getCommitterId()
	{
		return $this->committer_id;
	}
	
	public function getOwnerId()
	{
		return $this->owner_id;
	}
	
	public function getRepositoryId()
	{
		return $this->repository_id;
	}
	
	public function getOldRevision()
	{
		return $this->oldrev;
	}
	
	public function getNewRevision()
	{
		return $this->newrev;
	}
	
	public function getRefname()
	{
		return $this->refname;
	}
}