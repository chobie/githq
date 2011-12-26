<?php
class Git_Util
{
	public static function Blame($owner, $repository, $path)
	{
		$p  = escapeshellarg($path);
		$stat = `GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git blame -p master -- {$p}`;
		return Git\Util\Blame\Parser::parse($stat);
	}
	
	public static function Archive($owner, $repository, $tag)
	{
		$spec = array(
			0 => array("pipe","r"),
			1 => array("pipe","w")
		);

		$proc = proc_open("git archive --format zip {$tag}",$spec,$pipes,"/home/git/repositories/{$owner->getKey()}/{$repository->getId()}");
		if(is_resource($proc)) {
			fclose($pipes[0]);
			$content = stream_get_contents($pipes[1]);
			fclose($pipes[1]);
			proc_close($proc);
		}
		return $content;
	}
	
	public static function CommitLog($owner, $repository, $commit)
	{
		$n_commit  = escapeshellarg($commit);
		$stat = `GIT_DIR=/home/git/repositories/{$owner->getKey()}/{$repository->getId()} git log -p {$n_commit} -n1`;
		return Git\Util\Diff\Parser::parse($stat);
	}
}