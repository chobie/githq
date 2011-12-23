<?php
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

function inspect()
{
	$args = func_get_args();
	echo "<pre>";
	foreach($args as $arg) {
		var_dump($arg);
	}
	echo "</pre>";
}
