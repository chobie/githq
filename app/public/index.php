<?php
require __DIR__ . "/../vendor/php-uikit/UIKit/Framework/UIAutoLoader.php";
require __DIR__ . "/../config/development.php";

UIKit\Framework\UIAutoLoader::register();
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/libs');
UIKit\Framework\UIAutoLoader::add_include_path(dirname(__DIR__) . '/controllers');

UIKit\Framework\UIWebApplicationMain(null,null);
