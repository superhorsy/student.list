<?php
//Bootstrap file
require_once dirname(__FILE__) . '/../bootstrap.php';

//Обработчик исключений

/*(new \App\ErrorHandler())->run();

include ROOT  . '/../errors.php';*/

//Cессия
session_start();

// подключаем конфигурацию URL
$routes = ROOT . '/../routes.php';

// запускаем роутер
$router = new App\Router($routes);
$router->run();

