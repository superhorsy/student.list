<?php
use App\Router;

define('ROOT', __DIR__);
define('VIEWS_BASEDIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);

error_reporting(1);

//Composer autoload
require_once dirname(__DIR__)  . DIRECTORY_SEPARATOR . 'vendor'  . DIRECTORY_SEPARATOR . 'autoload.php';

//Session
session_start();

//Router
$router = Router::getInstance();
$router->run();