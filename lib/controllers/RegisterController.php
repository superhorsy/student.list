<?php

namespace App\Controllers;

use App\Utils;
use App\View;

class RegisterController
{

    public $view;

    function __construct()
    {
        //Registration token
        if (!isset($_SESSION['token_registration'])) {
            $_SESSION['token_registration'] = md5(uniqid(mt_rand(), true));
        }

        $this->view = new View();
    }

    public function action()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //CSRF protection
            if (!isset($_POST['token_registration']) || $_SESSION['token_registration'] !== $_POST['token_registration']) {
                $errors[] = 'Форма отправлена с фишингового сайта.';
            };
            $values = Utils::getRegistrationValues($_POST);
            $errors = Utils::validateValues($values);
        }
        $this->view->render('register', ['errors' => $errors, 'values' => $values]);
    }
}