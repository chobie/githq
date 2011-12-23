<?php
require __DIR__ . '/../config/env.php';

session_start();
UIKit\Framework\UIWebApplicationMain(null,null,'UIKit\Framework\HTTPFoundation\WebApplication','githqApplicationDelegate');