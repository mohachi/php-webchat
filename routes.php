<?php

use Mohachi\Router\HTTP\Request;
use Mohachi\Router\Router;
use OpenSwoole\Http\Response;

$router->get("/**", function(Request $req)
{
    dump($req->path);
    $req->pass();
});

$router->get("/", fn(Response $res) => $res->redirect("/login"));
$router->get("/login", fn(Response $res) => $res->sendfile(CLIENT_DIR . "/login.html"));

$router->post("/login", function(Request $req, Response $res) use ($sessionsTable)
{
    $id = $req->cookie[SESSION_NAME] ?? false;
    
    if( $id && $sessionsTable->exists($id) )
    {
        $res->redirect("/chat");
        return;
    }
    else
    {
        $res->cookie(SESSION_NAME, "", -1, "/");
    }
    
    $name = $req->post["name"] ?? false;
    
    if( ! $name || 0 === strcasecmp($name, "server") )
    {
        $res->redirect("/login");
        return;
    }
    
    $id = generateID();
    $sessionsTable->set($id, ["name" => $name]);
    $res->cookie(SESSION_NAME, $id, 0, "/");
    $res->redirect("/chat");
});

$router->get("/chat", function(Request $request, Response $response) use ($sessionsTable)
{
    $id = $request->cookie[SESSION_NAME] ?? false;
    
    if( ! is_string($id) || ! $sessionsTable->exists($id) )
    {
        $response->redirect("/login");
        return;
    }
    
    $response->sendfile(client("chat.html"));
});

$router->get("/**", function(Request $req, Response $response)
{
    if( file_exists(pub($req->path)) )
    {
        $response->sendfile(pub($req->path));
    }
});
