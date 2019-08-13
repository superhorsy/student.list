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

        $user = new User($_COOKIE['auth']);
        $notify = $_GET['notify'] ? strval($_GET['notify']) : '';

        $this->view->render('tournament', ['user' => $user, 'notify' => $notify]);

    }

    public function actionAdd(array $errors = null, array $values= null)
    {
        if (!isset($_COOKIE['auth'])) {
            header("Location: /index");
        }

        $values = [];
        $errors = [];
        $user = new User($_COOKIE['auth']);

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!$_POST['token_tournament']) {
                $errors[] = 'Форма отправлена со стороннего сайта.';
            } else {
                $values = Utils::getTournamentValues($_POST, $user->getId());
                $tournament = new Tournament();
                $tournament->hydrate($values);
                $errors = $tournament->isValid();
            }
            if (!$errors) {
                if (!($tournament->save())) {
                    $query = http_build_query(['notify' => 'fail']);
                    http_response_code(500);
                    header("Location: /tournament?$query");
                    exit;
                }
                http_response_code(302);
                $query = http_build_query(['notify' => 'success']);
                header("Location: /tournament?$query");
                exit;
            }
        }

        $this->view->render('tournament_add', ['user' => $user, 'errors' => $errors, 'values' => $values]);

    }
}