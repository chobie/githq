<?php
require_once "autoload.php";

if (!defined("REDIS_PORT")) {
	define("REDIS_PORT",6379);
}

require_once "functions.php";
require_once __DIR__ . '/../controllers/GitHQController.php';
