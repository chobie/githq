<?php
require_once __DIR__ . "/../vendor/php-uikit/UIKit/Framework/UIAutoLoader.php";
require_once __DIR__ . '/../vendor/twig/lib/Twig/Autoloader.php';
require_once __DIR__ . '/../vendor/Albino/src/Albino.php';
require_once __DIR__ . '/../vendor/php-sdk/src/facebook.php';

UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/controllers');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/models');
UIKit\Framework\UIAutoLoader::register();
Twig_Autoloader::register();

date_default_timezone_set('Asia/Tokyo');

require __DIR__ . "/../config/development.php";

class GitHQApplicationDelegate extends UIKit\Framework\UIWebApplicationDelegate
{
}


session_start();
UIKit\Framework\UIWebApplicationMain(null,null,'UIKit\Framework\UIWebApplication','GitHQApplicationDelegate');