<?php

namespace App\Controllers;

use App\models\User;
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
        $errors = [];
        $values = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //CSRF protection
            if (!isset($_POST['token_registration']) || $_SESSION['token_registration'] !== $_POST['token_registration']) {
                $errors[] = 'Произошла ошибка. Обновите страницу и попробуйте снова.';
            } else {
                $values = Utils::getValues($_POST);
                $user = new User($values);
                $errors = $user->validate();

                if (empty($errors)) {
                    $user->save($values);

                    http_response_code(302);
                    $query = http_build_query(['notify'=>'registered']);
                    header("Location: http://{$_SERVER['HTTP_HOST']}/index?$query");
                }
            }
        }
        $this->view->render('register', ['errors' => $errors, 'values' => $values]);

    }
}