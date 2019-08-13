<?php

define('ROOT', dirname(__FILE__));
define('VIEWS_BASEDIR', dirname(__FILE__).'/../views/');

error_reporting(1);

//composer autoload
require_once ROOT . '/../vendor/autoload.php';

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

