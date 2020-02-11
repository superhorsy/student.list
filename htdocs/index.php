<?php

use App\Components\Router;

define('ROOT', __DIR__);
define('VIEWS_BASEDIR', dirname(__DIR__) . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR);

if (!function_exists('array_key_first')) {
    function array_key_first(array $arr)
    {
        foreach ($arr as $key => $unused) {
            return $key;
        }
        return NULL;
    }
}

error_reporting(-1);

//Composer autoload
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

//Session
session_start();

//Router
$router = Router::getInstance();
$router->run();