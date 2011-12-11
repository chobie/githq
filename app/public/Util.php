<?php
namespace Git\Util\Blame;
class Commit
{
	protected $commit_id;
	protected $meta;
	
	public function getCommitId()
	{
		return $this->commit_id;
	}
	
	public function __construct($commit_id)
	{
		$this->commit_id = $commit_id;
	}
	
	public function add($key, $value)
	{
		$this->meta[$key] = $value;
	}
}

class Line
{
	protected $origin_number;
	protected $master_number;
	protected $line;
	
	public function __construct($origin,$master,$line)
	{
		$this->origin_number = $origin;
		$this->master_number = $master;
		$this->line = $line;
	}
}

class File
{
	protected $commits;
	protected $groups;
	
	public function addCommit(Commit $meta)
	{
		$this->commits[$meta->getCommitId()] = $meta;
	}
	
	public function addGroup($group)
	{
		$this->groups[] = $group;
	}
}
class Group
{
	protected $expected;
	protected $commit_id;
	protected $lines;
	
	public function __construct($expected)
	{
		$this->expected = $expected;
	}
	
	public function setCommitId($commit_id)
	{
		$this->commit_id = $commit_id;
	}
	
	public function add($string)
	{
		$this->lines[] = $string;
	}
}
class Parser
{
	public static function parse($string)
	{
		$file = new File();
		$lines = explode("\n",$string);

		$meta   = array();
		$result = array();
		$commits = array();
		
		foreach($lines as $line) {
			if(preg_match("/^(?P<commit_id>[a-zA-Z0-9]{40})\s(?P<old>\d+)\s(?P<new>\d+)(?P<group>\s\d+)?$/",$line,$match)) {
				$commit_id = $match['commit_id'];
				$old   = (int)$match['old'];
				$new   = (int)$match['new'];
				
				if(isset($match['group'])){
					$continuous = $match['group'];
				} else {
					$continuous = null;
				}
				
				if (!isset($commits[$commit_id])) {
					$commits[$commit_id] = new Commit($commit_id);
					$file->addCommit($commits[$commit_id]);
				}
				
				if ($continuous > 0) {
					$meta[$commit_id] = array();
					$group = new Group($continuous);
					$group->setCommitId($commit_id);
					$file->addGroup($group);
					
					$result[$commit_id] = true;
				}
			} else {
				if(strpos($line,"\t") === 0) {
					list(, $value) = explode("\t",$line,2);
					$result[] = array(
						"commit_id" => $commit_id,
						"lines" => $value
					);
					$group->add(new Line($old,$new, $value));
				} else if($line == '') {
				} else if($line == 'boundary') {
				} else {
					list($key, $value) = explode(" ",$line,2);
					$commits[$commit_id]->add($key,$value);
				}
			}
		}
		
		return $file;
		return $result;
	}
}

$string = file_get_contents("blame");
$result = Parser::parse($string);

var_dump($result);
