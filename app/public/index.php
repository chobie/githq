<?php
require __DIR__ . '/../config/env.php';

session_start();

UIKit\Framework\HTTPFoundation\WebApplication\Main(null,null,
	'UIKit\Framework\HTTPFoundation\WebApplication',
	'githqApplicationDelegate'
);
