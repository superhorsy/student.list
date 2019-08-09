<?php

namespace App\Controllers;

use App\models\User;
use App\models\UserTDG;
use App\Utils;

class IndexController
{

    public $view;

    function __construct()
    {
        //Login token
        if (!isset($_SESSION['token_login'])) {
            $_SESSION['token_login'] = md5(uniqid(mt_rand(), true));
        }

        $this->view = new \App\View();
    }

    public function action()
    {
        $notify = $_GET['notify'] ?? '';
        $errors = [];
        $values = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //CSRF protection
            if (!isset($_POST['token_login']) || $_SESSION['token_login'] !== $_POST['token_login']) {
                $errors[] = 'Произошла ошибка. Обновите страницу и попробуйте снова.';
            } else {
                $values = Utils::getValues($_POST);
                if (empty($values['password'] || $values['username'])) {
                    $errors[] = 'Необходимые поля не заполнены.';
                } else {
                    $userTDG = new UserTDG();
                    $user = $userTDG->getUserByUsername($values['username']);
                    if (!$user) {
                        $errors[] = "Пользователь {$values['username']} не найден";
                    } elseif (!password_verify($values['password'], $user->hash)) {
                        $errors[] = "Введен неправильный пароль";
                    }
                }
            }

            if (empty($errors)) {
                $notify = 'logined';
                if (isset($_POST['rememberme']) && $_POST['rememberme'] === 'true') {
                    setcookie('auth', $user->id, time() + 10 * 365 * 12 * 60 * 60, '/', '', false, true);
                } else {
                    setcookie('auth', $user->id, 0, '/', '', false, true);
                }
                if(!$_SESSION['token_logout']) {
                    $_SESSION['token_logout'] = md5(uniqid(mt_rand(),true));
                }
            }
        };

        $this->view->render('index', [
            'notify' => $notify,
            'errors' => $errors,
            'values' => $values
        ]);
    }

    public function actionLogout()
    {

        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            //CSRF protection
            if (isset($_POST['token_logout']) || $_SESSION['token_logout'] !== $_POST['token_logout']) {
                setcookie('auth',null,-1, '/');
                $notify = 'logout';
                session_unset();
                session_destroy();
                header("Location: ../index");
                }
            }

        $this->view->render('index', [
            'notify' => $notify
        ]);
    }
}