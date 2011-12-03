<?php
use UIKit\Framework\UIStoredConfig;
use UIKit\Framework\UIStoredUnderlying;

$config = UIStoredConfig::getInstance();
$config->set("user", array(
     "strategy"     => "UIKit\\Framework\\UIStoredRedisStrategy",
     "serializer"   => "UIKit\\Framework\\UIStoredPHPSerializer",
     "cache"        => "UIKit\\Framework\\UIStoredPHPArrayCache",
     "expiration"   => 0,
     "lock_timeout" => 5,
     "redis"        => array(
          "host" => "localhost",
          "port" => 6379,
          "persistence" => true,
     )
));

$i = UIStoredUnderlying::getInstance();
foreach ($config->keys() as $key) {
     $i->addStrategy($key, $config->get($key . ".strategy"));
}
