<?php


namespace App\controllers;


use App\models\Tournament;
use App\models\User;
use App\models\UserTDG;
use App\Utils;

class TournamentController
{

    public $view;

    function __construct()
    {
        $this->view = new \App\View();
        if (!$_SESSION['token_tournament']) {
            $_SESSION['token_tournament'] = md5(uniqid(mt_rand(), true));
        }

    }

    public function action()
    {
        if (!isset($_COOKIE['auth'])) {
            header("Location: /index");
        }

        $values = [];
        $errors = [];
        $user = new User($_COOKIE['auth']);

        if ($_SERVER['REQUEST_METHOD'] == 'post') {
            if (!$_POST['token_tournament']) {
                $errors[] = 'Форма отправлена со стороннего сайта.';
            } else {
                $values = Utils::getTournamentValues($_POST);
                $tournament = new Tournament();
                $tournament->hydrate($values);
                $errors = $tournament->isValid();
            }
        }

        $this->view->render('tournament', ['user' => $user, 'values' => $values, $errors => $errors]);

    }

    public function actionAdd()
    {
        if (!isset($_COOKIE['auth'])) {
            header("Location: /index");
        }

        $user = new User($_COOKIE['auth']);

        $this->view->render('tournament_add', ['user' => $user]);

    }
}