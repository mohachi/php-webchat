<?php

use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\WebSocket\Server;

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
