<?php

use App\Router;

//Bootstrap file
require_once dirname(__FILE__) . '/../bootstrap.php';

//Обработчик исключений

/*(new \App\ErrorHandler())->run();

include ROOT  . '/../errors.php';*/

//Cессия
session_start();

// запускаем роутер
$router = Router::getInstance();
$router->run();

