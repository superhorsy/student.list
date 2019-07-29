<?php

define('ROOT', dirname(__FILE__));
define('VIEWS_BASEDIR', dirname(__FILE__).'/../views/');

//composer autoload
require_once ROOT . '/../vendor/autoload.php';

/*//Обработчик исключений

set_exception_handler(function (Throwable $exception) {
    // Функция будет вызвана, если исключение не будет
    // поймано и завершит программу.
    //
    // Она может записать исключение в журнал и вывести
    // страницу ошибки.
    error_log($exception->__toString());

    header("HTTP/1.0 503 Temporary unavailable");
    header("Content-type: text/plain; charset=utf-8");
    echo "Извините, на сайте произошла ошибка.\n";
    echo "Попробуйте перезагрузить страницу.\n";
});

set_error_handler(function ($errno, $errstr, $errfile, $errline ) {
    // Не выбрасываем исключение если ошибка подавлена с
    // помощью оператора @
    if (!error_reporting()) {
        return;
    }
    throw new ErrorException($errstr, $errno, 0, $errfile, $errline);
});*/

// подключаем конфигурацию URL
$routes = ROOT . '/../routes.php';

// запускаем роутер
$router = new App\Router($routes);
$router->run();

