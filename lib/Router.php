<?php

namespace App;

class Router
{
    private static $_instance = null;
    private $routes;

    // Хранит конфигурацию маршрутов.
    private $action;

    private function __construct()
    {
        $routes = Config::getInstance()->routes;
        // Получаем конфигурацию из файла.
        $this->routes = include($routes);
    }

    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return mixed
     */
    public function getAction()
    {
        return $this->action;
    }

    public function run()
    {
        // Parse URL
        $path = $this->getPath();

        // Пытаемся применить к нему правила из конфигуации.
        foreach ($this->routes as $pattern => $route) {
            // Если правило совпало.
            if (preg_match("~$pattern~", $path)) {
                // Получаем внутренний путь из внешнего согласно правилу.
                $internalRoute = preg_replace("~$pattern~", $route, $path);
                // Разбиваем внутренний путь на сегменты.
                $segments = explode('/', $internalRoute);
                // Первый сегмент — контроллер.
                $controller = 'App\\Controllers\\' . ucfirst(array_shift($segments)) . 'Controller';
                // Второй — действие.
                $this->action = 'action' . ucfirst(array_shift($segments));
                // Остальные сегменты — параметры.
                $parameters = $segments;

                // Если не загружен нужный класс контроллера или в нём нет
                // нужного метода — 404
                if (!is_callable([$controller, $this->action])) {
                    header("HTTP/1.0 404 Not Found");
                    return;
                }
                //Создаем объект контроллера
                $controllerObject = new $controller;
                // Вызываем действие контроллера с параметрами
                call_user_func_array(array($controllerObject, $this->action), $parameters);
                return;
            }
        }

        // Ничего не применилось. 404.
        header("HTTP/1.0 404 Not Found");
        return;
    }

    /**
     * Метод получает URI. Несколько вариантов представлены для надёжности.
     * @return string
     */
    public function getPath()
    {
        if (!empty($_SERVER['REQUEST_URI'])) {
            return trim($_SERVER['REQUEST_URI'], '/');
        }

        if (!empty($_SERVER['PATH_INFO'])) {
            return trim($_SERVER['PATH_INFO'], '/');
        }

        if (!empty($_SERVER['QUERY_STRING'])) {
            return trim($_SERVER['QUERY_STRING'], '/');
        }
    }
}