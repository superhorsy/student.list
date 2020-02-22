<?php

namespace App\Controllers;

use App\Components\Utils;
use App\Components\View;
use App\Models\User\User;

class IndexController
{

    public $view;

    function __construct()
    {
        //Login token
        if (!isset($_SESSION['token_login'])) {
            $_SESSION['token_login'] = md5(uniqid(mt_rand(), true));
        }

        $this->view = new View();
    }

    public function action()
    {
        $notify = $_GET['notify'] ?? '';
        $errors = [];
        $values = [];
        $user = '';

        if (isset($_COOKIE['auth'])) {
            $user = new User($_COOKIE['auth']);
        } elseif ($_SERVER['REQUEST_METHOD'] == 'POST') {
            //CSRF protection
            if (!isset($_POST['token_login']) || $_SESSION['token_login'] !== $_POST['token_login']) {
                $errors[] = 'Произошла ошибка. Обновите страницу и попробуйте снова.';
            } else {
                $values = Utils::getUserValues($_POST);
                if (empty($values['password'] || $values['username'])) {
                    $errors[] = 'Необходимые поля не заполнены.';
                } else {
                    $user = new User($values['username']);
                    if (!$user) {
                        $errors[] = "Пользователь {$values['username']} не найден";
                    } elseif (!password_verify($values['password'], $user->getHash())) {
                        $errors[] = "Введен неправильный пароль";
                    }
                }
            }

            if (!empty($errors)) {
                unset($user);
            }

            if (empty($errors)) {
                if (isset($_POST['rememberme']) && $_POST['rememberme'] === 'true') {
                    setcookie('auth', $user->getId(), time() + 10 * 365 * 12 * 60 * 60, '/', '', false, true);
                } else {
                    setcookie('auth', $user->getId(), 0, '/', '', false, true);
                }
                if (!isset($_SESSION['token_logout'])) {
                    $_SESSION['token_logout'] = md5(uniqid(mt_rand(), true));
                }
                http_response_code(302);
                $query = http_build_query(['notify' => 'logined']);
                header("Location: /index?$query");
            }
        }

        $this->view->render('index', [
            'notify' => $notify,
            'errors' => $errors,
            'values' => $values,
            'user' => $user
        ]);
    }

    public function actionLogout()
    {
        //CSRF protection
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_SESSION['token_logout'])) {
            if (!($_SESSION['token_logout'] !== $_POST['token_logout'])) {
                setcookie('auth', null, -1, '/');
                session_unset();
                session_destroy();
                session_cache_expire();
            }
        }

        header("Location: /index");
        exit;
    }

}