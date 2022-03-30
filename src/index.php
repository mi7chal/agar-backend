<?php
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use MyApp\GameHandler;


require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * tworzy serwer websocket
 */
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new GameHandler()
        )
    ),
    8081
);

$server->run();