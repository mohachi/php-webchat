<?php

use OpenSwoole\WebSocket\Server;
use Random\Randomizer;

function pub(string $file)
{
    return PUBLIC_DIR . "$file";
}

function client(string $file)
{
    return CLIENT_DIR . "/$file";
}

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
