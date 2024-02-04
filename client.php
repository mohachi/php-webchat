<?php

use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Http\Client;
use OpenSwoole\Coroutine\System;

require_once __DIR__ . "/vendor/autoload.php";

Coroutine::run(function()
{
    $client = new Client("127.0.0.1", 9501);
    $client->get("/login");
    $client->set([
        "keep_alive" => true
    ]);

    if( $client->upgrade("/") )
    {
        while( true )
        {
            $client->push("welcome");
            dump($client->recv());
            System::sleep(2);
        }
    }
    
});
