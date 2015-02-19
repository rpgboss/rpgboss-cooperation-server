<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\App;
use RpgbossCoopServer\Chat;

// for testing
$host = "localhost";
// live
$host = "assets.rpgboss.com";

$server = new App($host,8080,"0.0.0.0",null);
$server->route('/chat', new Chat(),array("*"));
$server->run();