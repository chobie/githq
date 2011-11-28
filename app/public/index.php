<?php
require __DIR__ . "/../vendor/php-uikit/UIKit/Framework/UIAutoLoader.php";
require_once __DIR__ . '/../vendor/twig/lib/Twig/Autoloader.php';

UIKit\Framework\UIAutoLoader::register();
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/controllers');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/models');
Twig_Autoloader::register();

require __DIR__ . "/../config/development.php";
UIKit\Framework\UIWebApplicationMain(null,null);
