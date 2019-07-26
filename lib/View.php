<?php

namespace App;

class View {
    // получить отрендеренный шаблон с параметрами $params
    function fetchPartial($template, $params = array()){
        extract($params);
        ob_start();
        include VIEWS_BASEDIR . $template.'.phtml';
        return ob_get_clean();
    }

    // вывести отрендеренный шаблон с параметрами $params
    function renderPartial($template, $params = array()){
        echo $this->fetchPartial($template, $params);
    }

    // получить отрендеренный в переменную $content layout-а
    // шаблон с параметрами $params
    function fetch($template, $params = array()){
        $content = $this->fetchPartial($template, $params);
        return $this->fetchPartial('layout', array('content' => $content));
    }

    /**вывести отрендеренный в переменную $content layout-а
     * шаблон с параметрами $params
     * @param $template
     * @param array $params
     */
    function render($template, $params = array()){
        echo $this->fetch($template, $params);
    }
}
