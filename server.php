<?php

use Mohachi\Router\Router;
use OpenSwoole\Table;
use OpenSwoole\WebSocket\Server;

require_once __DIR__ . "/vendor/autoload.php";

$opts = getopt("p:");
$port = $opts["p"] ?? 9502;

$sessionsTable = new Table(1024);
$sessionsTable->column("name", Table::TYPE_STRING, 32);
$sessionsTable->create();

$fdSessionsTable = new Table(1024);
$fdSessionsTable->column("id", Table::TYPE_STRING, 16);
$fdSessionsTable->create();

$server = new Server("0.0.0.0", $port);
$router = new Router($server);

$server->on("start", function(Server $server)
{
    echo "Server start listenning: http://localhost:{$server->port}\n";
});

require_once __DIR__ . "/routes.php";
require_once __DIR__ . "/websocket.php";

$router->register();
$server->start();
