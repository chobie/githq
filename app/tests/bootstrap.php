<?php
define("REDIS_PORT",16379);
require __DIR__ . "/../config/env.php";
require "models/Mock_TestCase.php";

/*
# how to exe redis-server...?
# /usr/local/Cellur/redis/2.2.4/bin/redis-server - 
daemonize no
pidfile /tmp/uhi.pid
port 16379
bind 127.0.0.1
timeout 300
loglevel debug
logfile stdout
syslog-enabled no
save 900 1
save 300 10
save 60 10000
dbfilename /tmp/tmp.rdb
dir /tmp/

Ctrl-D
*/