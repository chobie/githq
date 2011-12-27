<?php
require_once __DIR__ . "/../vendor/php-uikit/UIKit/Framework/AutoLoader.php";
require_once __DIR__ . '/../vendor/php-sdk/src/facebook.php';

UIKit\Framework\AutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\AutoLoader::add_include_path(dirname(__DIR__) . '/controllers');
UIKit\Framework\AutoLoader::add_include_path(dirname(__DIR__) . '/views');
UIKit\Framework\AutoLoader::add_include_path(dirname(__DIR__) . '/models');
UIKit\Framework\AutoLoader::registerNameSpaces(array(
	'Monolog' => dirname(__DIR__) .'/vendor/monolog/src',
	'Git' => dirname(__DIR__) . '/vendor/Git_Util/src/',
	'Albino' => dirname(__DIR__) . '/vendor/Albino/src/',
	'chobie\\VersionSorter' => dirname(__DIR__) . '/vendor/VersionSorter/src/',
	'Twig' => dirname(__DIR__) . '/vendor/twig/lib/',
));
UIKit\Framework\AutoLoader::register();
