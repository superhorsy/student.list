<?php

namespace App;

use Twig_Environment;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;
use Twig_Loader_Filesystem;

class View
{
    private $twig;

    public function __construct()
    {
        $loader = new Twig_Loader_Filesystem([VIEWS_BASEDIR, ROOT]);
        $this->twig = new Twig_Environment($loader, ['cache' => VIEWS_BASEDIR . 'cache', 'debug' => true]);
    }

    /**
     * Получить отрендеренный шаблон с параметрами $params
     * @param $template
     * @param array $params
     * @return false|string
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    function fetchPartial($template, $params = array())
    {
        $params = array_merge($params, ['session' => $_SESSION]);

        if (file_exists(VIEWS_BASEDIR . $template . '.phtml')) {
            extract($params);
            ob_start();
            include VIEWS_BASEDIR . $template . '.phtml';
            return ob_get_clean();
        } else if (file_exists(VIEWS_BASEDIR . $template . '.twig')) {
            return $this->twig->render($template . '.twig', $params);
        }
    }

    /**
     * Вывести отрендеренный шаблон с параметрами $params
     * @param $template
     * @param array $params
     */
    function renderPartial($template, $params = array())
    {
        echo $this->fetchPartial($template, $params);
    }

    /**
     * Получить отрендеренный в переменную $content layout-а шаблон с параметрами $params
     * @param $template
     * @param array $params
     * @return false|string
     */
    function fetch($template, $params = array())
    {
        $content = $this->fetchPartial($template, $params);
        return $this->fetchPartial('layout', array('content' => $content));
    }

    /**
     * Вывести отрендеренный в переменную $content layout-а шаблон с параметрами $params
     * @param $template
     * @param array $params
     */
    function render($template, $params = array())
    {
        echo $this->fetch($template, $params);
    }
}
