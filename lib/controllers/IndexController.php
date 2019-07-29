<?php

namespace App\Controllers;

class IndexController {

    public $view;

    function __construct(){
        // используем наш View, описанный ранее
        $this->view = new \App\View();
    }

    public function action() {
        $this->view->render('index');
    }
}