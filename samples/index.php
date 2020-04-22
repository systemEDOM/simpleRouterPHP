<?php

require_once "../vendor/autoload.php";

use EDOM\SimpleRouterPHP\Router;

$router = new Router("EDOM\\");

$router->get('/', function () {
    echo "get";
});
$router->post('/', function () {
    echo "post";
});
$router->put('/', function () {
    echo "put";
});
$router->delete('/', function () {
    echo "delete";
});
$router->get('/users/[i:id]/hi/[i:slug]', function () {
    echo "params";
});
$router->get('/hi/[i:id]/[i:slug]', "HomeController@showHome");


$router->match();