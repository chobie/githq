<?php
require_once __DIR__ . "/../vendor/php-uikit/UIKit/Framework/UIAutoLoader.php";
require_once __DIR__ . '/../vendor/twig/lib/Twig/Autoloader.php';
require_once __DIR__ . '/../vendor/Albino/src/Albino.php';
require_once __DIR__ . '/../vendor/php-sdk/src/facebook.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Line.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Lines.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Parser.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Struct.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/File.php';
require_once __DIR__ . '/../vendor/Text_Diff/src/Text/Diff/Hunk.php';
require_once __DIR__ . '/../vendor/Git_Util/src/Git/Util/Blame/Parser.php';
require_once __DIR__ . '/../vendor/Git_Util/src/Git/Util/Blame/Commit.php';
require_once __DIR__ . '/../vendor/Git_Util/src/Git/Util/Blame/File.php';
require_once __DIR__ . '/../vendor/Git_Util/src/Git/Util/Blame/Group.php';
require_once __DIR__ . '/../vendor/Git_Util/src/Git/Util/Blame/Line.php';

define('FACEBOOK_APPLICATION_ID','__YOU_MUST_DEFINE_YOUR_APP_ID');
define('FACEBOOK_APPLICATION_SECRET','__YOU_MUST_DEFINE_YOUR_APP_SECRET');
if (!defined("REDIS_PORT")) {
	define("REDIS_PORT",6379);
}

UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/controllers');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/models');
UIKit\Framework\UIAutoLoader::register();
Twig_Autoloader::register();

function hash_diff($array1, $array2, $strict = false) {
	$diff = array();
	$is_hash = determine_array_type($array1);

	if ($is_hash) {
		foreach ($array1 as $key => $value) {
			if (!array_key_exists($key,$array2)) {
				$diff['-'][$key] = $value;
			} elseif (is_array($value)) {
				if (!is_array($array2[$key])) {
					$diff['-'][$key] = $value;
					$diff['+'][$key] = $array2[$key];
				} else {
					$new = hash_diff($value, $array2[$key], $strict);
					if ($new !== false) {
						if (isset($new['-'])){
							$diff['-'][$key] = $new['-'];
						}
						if (isset($new['+'])){
							$diff['+'][$key] = $new['+'];
						}
					}
				}
			} elseif ($strict && $array2[$key] != $value) {
				$diff['-'][$key] = $value;
				$diff['+'][$key] = $array2[$key];
			} elseif ($strict && $array2[$key] == $value) {
				/** nothing to do */
			} elseif (!$strict && $array2[$key] != $value) {
				/** nothing to do */
			} elseif (!$strict && $array2[$key] == $value) {
				/** nothing to do */
			} else {
				throw new Exception('unexpected type');
			}
		}

		foreach ($array2 as $key => $value) {
			if ($is_hash && !array_key_exists($key,$array1)) {
				$diff['+'][$key] = $value;
			}
		}
	} else {
		$tmp = array_diff($array1,$array2);
		foreach($tmp as $item) {
			$diff['-'][] = $item;
		}
		$tmp = array_diff($array2,$array1);
		foreach($tmp as $item) {
			$diff['+'][] = $item;
		}
	}

	return $diff;
}

function determine_array_type($array)
{
	if((bool)$array) {
		$idx = 0;
		foreach($array as $key => $value) {
			if(is_string($key)){
				return 1;
			} else {
				if ($key != $idx) {
					return 1;
				}
			}
			$idx++;
		}
	}

	return 0;
}


date_default_timezone_set('Asia/Tokyo');

class Twig_Filter_Sundown extends \Twig_Extension
{
	public function getName()
	{
		return 'sundown';
	}

	public function getFilters()
	{
		return array(
			'sundown' => new \Twig_Filter_Function('twig_sundown_filter'),
		);
	}
}

function twig_sundown_filter($string)
{
	$sundown = new Sundown($string);
	return $sundonw->to_html();
	
}

class VersionSorter
{
	private static function scan_state_get($c)
	{
		$c = (string)$c;
		if (ctype_digit($c)) {
			return 0;
		} else if (ctype_alpha($c)) {
			return 1;
		} else {
			return 2;
		}
	}

	private static function parse_version_word($vsi)
	{
		$start = $end = $size =0;
		$max = strlen($vsi);
		$res = array();
		while($start < $max) {
			$current_state = self::scan_state_get($vsi[$start]);
			if ($current_state == 2) {
				$start++;
				$end = $start;
				continue;
			}
				
			do {
				$end++;
				$next_char = @$vsi[$end];
				$next_state = self::scan_state_get($next_char);
			} while($current_state == $next_state);
			$size = $end - $start;
			$res[] = substr($vsi,$start,$size);
				
			$start = $end;
		}
		return $res;
	}

	public static function compare_by_version($a, $b)
	{
		return strcmp($a,$b);
	}


	public static function sort($array)
	{
		$widest = 0;
		$result = array();
		foreach($array as $item) {
			$vsi = self::parse_version_word($item);
			foreach($vsi as $it) {
				$tmp = strlen($it);
				if($widest < $tmp) {
					$widest = $tmp;
				}
			}
			$result[$item] =$vsi;
		}

		$normalized = array();
		foreach($result as $key => $item) {
			foreach($item as $b) {
				$length = strlen($b);
				if(ctype_digit((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}
				@$normalized[$key] .= $b;
				if(ctype_alpha((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}

			}
		}
		asort($normalized);
		return array_keys($normalized);
	}

	public static function rsort($array)
	{
		$widest = 0;
		$result = array();
		foreach($array as $item) {
			$vsi = self::parse_version_word($item);
			foreach($vsi as $it) {
				$tmp = strlen($it);
				if($widest < $tmp) {
					$widest = $tmp;
				}
			}
			$result[$item] =$vsi;
		}

		$normalized = array();
		foreach($result as $key => $item) {
			foreach($item as $b) {
				$length = strlen($b);
				if(ctype_digit((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}
				@$normalized[$key] .= $b;
				if(ctype_alpha((string)$b[0])) {
					for($i=0;$i<$widest - $length;$i++) {
						@$normalized[$key] .= ' ';
					}
				}

			}
		}
		arsort($normalized);
		return array_keys($normalized);
	}
}


function inspect()
{
	$args = func_get_args();
	echo "<pre>";
	foreach($args as $arg) {
		var_dump($arg);
	}
	echo "</pre>";
}


/* loading redis configrations. */
$data = file_get_contents(__DIR__ . "/../config/entities.xml");
$xml = simplexml_load_string($data);

$result = array();
foreach($xml->xpath("//entity") as $element) {
	$id = (string)$element->attributes()->id;
	$result[$id]['strategy']      = (string)$element->strategy;
	$result[$id]['serializer']   = (string)$element->serializer;
	$result[$id]['cache']        = (string)$element->cache;
	$result[$id]['expiration']   = (string)$element->expiration;
	$result[$id]['lock_timeout'] = (string)$element->lock_timeout;

	foreach ($element->redis as $redis) {
		$result[$id]['redis'] = array(
					"host" => (string)$redis->host,
					"port" => (string)$redis->port,
					"persistence" => (string)$redis->persistence,
		);
	}
}

$conf= UIKit\Framework\UIStoredConfig::getInstance();
foreach($result as $key => $config) {
	$conf->set($key,$config);
}

$i = UIKit\Framework\UIStoredUnderlying::getInstance();
foreach ($conf->keys() as $key) {
	$i->addStrategy($key, $conf->get($key . ".strategy"));
	$i->addSerializer($key, $conf->get($key . ".serializer"));
	$i->addCache($key,$conf->get($key . '.cache'));
}
/* end loading */

require_once __DIR__ . '/../controllers/GitHQController.php';

