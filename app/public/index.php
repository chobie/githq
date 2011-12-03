<?php
require_once __DIR__ . "/../vendor/php-uikit/UIKit/Framework/UIAutoLoader.php";
require_once __DIR__ . '/../vendor/twig/lib/Twig/Autoloader.php';
require_once __DIR__ . '/../vendor/Albino/src/Albino.php';

UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/controllers');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/models');
UIKit\Framework\UIAutoLoader::register();
Twig_Autoloader::register();

require __DIR__ . "/../config/development.php";

class GitHQApplicationDelegate extends UIKit\Framework\UIWebApplicationDelegate
{
}


session_start();
UIKit\Framework\UIWebApplicationMain(null,null,'UIKit\Framework\UIWebApplication','GitHQApplicationDelegate');