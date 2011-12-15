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

require "development.php";

