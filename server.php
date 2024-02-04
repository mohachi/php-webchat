<?php

use OpenSwoole\Http\Request;
use OpenSwoole\Http\Response;
use OpenSwoole\Table;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;
use Random\Randomizer;

require_once __DIR__ . "/vendor/autoload.php";

define("PUBLIC_DIR", __DIR__ . "/public");
define("CLIENT_DIR", __DIR__ . "/client");
define("SESSION_NAME", "PHPSESSID");
define("SESSION_ID_LENGTH", 16);

$opts = getopt("p:");
$port = $opts["p"] ?? 9502;

$sessionsTable = new Table(1024);
$sessionsTable->column("name", Table::TYPE_STRING, 32);
$sessionsTable->create();

$fdSessionsTable = new Table(1024);
$fdSessionsTable->column("id", Table::TYPE_STRING, 16);
$fdSessionsTable->create();

$server = new Server("0.0.0.0", $port);

$server->on("start", function(Server $server)
{
    echo "Server start listenning: http://localhost:{$server->port}\n";
});

$server->on("request", function(Request $request, Response $response) use ($server, $sessionsTable)
{
    $id = $request->cookie[SESSION_NAME] ?? null;
    $path = substr($request->server["path_info"], 1);
    
    dump($path);
    
    if( $path == "" )
    {
        $response->redirect("/login");
        return;
    }
    
    if( $path == "login")
    {
        if( $request->getMethod() == "POST" )
        {
            if( ! $sessionsTable->exists($id) )
            {
                $id = generateID();
            }
            
            $name = $request->post["name"] ?? false;
            if( ! $name || strcasecmp($name, "server") == 0 )
            {
                $response->redirect("/login");
                return;
            }
            
            $sessionsTable->set($id, ["name" => $name]);
            $response->cookie(SESSION_NAME, $id, 0, "/");
            $response->redirect("/chat");
            return;
        }
        
        $response->sendfile(CLIENT_DIR . "/login.html");
        return;
    }
    
    if( $path == "chat" )
    {
        if( $id == null || ! $sessionsTable->exists($id) )
        {
            $response->redirect("/login");
            return;
        }
        
        $response->sendfile(CLIENT_DIR . "/chat.html");
        return;
    }
    
    if( is_file(PUBLIC_DIR . "/$path") )
    {
        $response->sendfile(PUBLIC_DIR . "/$path");
        return;
    }
    
    $response->status(404);
});

$server->on("open", function(Server $server, Request $request) use ($sessionsTable, $fdSessionsTable)
{
    $id = $request->cookie[SESSION_NAME] ?? null;
    $name = $sessionsTable->get($id)["name"];
    
    $fdSessionsTable->set($request->fd, [
        "id" => $id
    ]);
    
    $server->push($request->fd, json_encode([
        "author" => "Server",
        "text" => "Welcome $name"
    ]));
    
    broadcast($server, json_encode([
        "author" => "Server",
        "text" => "Everyone please welcome $name"
    ]), $request->fd);
});

$server->on("message", function(Server $server, Frame $frame) use ($sessionsTable, $fdSessionsTable)
{
    $id = $fdSessionsTable->get($frame->fd)["id"];
    $name = $sessionsTable->get($id)["name"];
    
    $server->push($frame->fd, json_encode([
        "author" => "you:$name",
        "text" => true
    ]));
    
    broadcast($server, json_encode([
        "author" => $name,
        "text" => $frame->data
    ]), $frame->fd);
});

$server->start();

function broadcast(Server $server, string $data, int ...$except)
{
    foreach( $server->connections as $fd )
    {
        $ws_status = $server->getClientInfo($fd)["websocket_status"];
        
        if( $ws_status == Server::WEBSOCKET_STATUS_ACTIVE && ! in_array($fd, $except) )
        {
            $server->push($fd, $data);
        }
    }
}

function generateID()
{
    return (new Randomizer())->getBytesFromString("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz", SESSION_ID_LENGTH);
}
