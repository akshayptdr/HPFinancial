<?php
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;
use App\Core\Session;
use App\Core\Request;

Session::start();

$router = new Router();
(require CONFIG_PATH . '/routes.php')($router);

$router->dispatch(Request::method(), Request::path());
