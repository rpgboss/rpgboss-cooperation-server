<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Ratchet\Http\HttpServer;
use Ratchet\Server\IoServer;
use Ratchet\WebSocket\WsServer;
use RpgbossCoopServer\Commander;

$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new Commander()
        )
    ),
    8080
);

$server->run();
