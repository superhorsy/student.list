<?php


namespace App\controllers;


use App\models\Tournament;
use App\models\TournamentTDG;
use App\models\User;
use App\models\UserTDG;
use App\Utils;

class TournamentController
{

    public $view;
    private $user;
    private $tdg;

    function __construct()
    {
        $this->view = new \App\View();
        $this->tdg = new TournamentTDG();
        if (!$_SESSION['token_tournament']) {
            $_SESSION['token_tournament'] = md5(uniqid(mt_rand(), true));
        }

        if (!isset($_COOKIE['auth'])) {
            header("Location: /index");
            exit;
        }
        $this->user = new User($_COOKIE['auth']);
        $notify = $_GET['notify'] ? strval($_GET['notify']) : '';

    }

    public function action()
    {
        $tournaments = (new TournamentTDG())->getTournamentsByUser($this->user->getId());

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$_POST['token_tournament']) {
                $errors[] = 'Форма отправлена со стороннего сайта.';
            } else {
                if (isset($_POST['delete']) && $_POST['delete']) {
                    (new TournamentTDG())->deleteTournamentById($_POST['delete']);
                    $tournaments = (new TournamentTDG())->getTournamentsByUser($this->user->getId());
                }
            }
        }
        $this->view->render('tournament', ['user' => $this->user, 'notify' => $notify, 'tournaments' => $tournaments]);

    }

    public function actionShow($tournamentId)
    {

        $tournament = (new TournamentTDG())->getTournamentById($tournamentId);
        $errors = [];

        if (!$tournament || $tournament->getOwnerId() !== $this->user->getId()) {
            $query = http_build_query(['notify' => 'fail']);
            http_response_code(500);
            header("Location: /tournament?$query");
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            if (!$_POST['token_tournament']) {
                $errors[] = 'Форма отправлена со стороннего сайта.';
            } else {
                if ($_POST['tournament_action'] === 'start') {
                    $tournament->start();

                } elseif ($_POST['tournament_action'] === 'next') {

                    $playersPlaying = count($tournament->getPlayers()) - count($tournament->getWaitingPlayers()) - count($tournament->getLoosers());

                    if (!(isset($_POST['loosers']) && !empty($_POST['loosers']) && is_array($_POST['loosers']))) {
                        $errors[] = 'Не отмечены проигравшие';
                    } elseif (count($_POST['loosers']) != ($playersPlaying) / 10) {
                        $errors[] = 'Отмечены не все проигравшие';
                    }

                    if (!$errors) {
                        $roundResult = [
                            'loosers' => $_POST['loosers'] ?? '',
                        ];

                        $tournament->next($roundResult);
                    }
                } elseif ($_POST['tournament_action'] === 'reset') {
                    $tournament->reset();
                } elseif ($_POST['tournament_action'] === 'send_home') {
                    $data = [
                        'sendHome' => $_POST['sendHome'] ?? '',
                    ];
                    $tournament->sendHome($data);
                }
            }
        }

        $this->view->render('tournament_show', ['user' => $this->user, 'notify' => $notify ?? false, 'tournament' => $tournament, 'errors' => $errors]);

    }

    public function actionAdd(array $errors = null, array $values = null)
    {

        $values = [];
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!$_POST['token_tournament']) {
                $errors[] = 'Форма отправлена со стороннего сайта.';
            } else {
                $values = Utils::getTournamentValues($_POST, $this->user->getId());
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

        $this->view->render('tournament_add', ['user' => $this->user, 'errors' => $errors, 'values' => $values]);

    }

    public function actionEdit(int $tournamentId)
    {

        $values = [];

        $tournament = $this->tdg->getTournamentById($tournamentId);
        if ($tournament->getOwnerId() !== $this->user->getId()) {
            $query = http_build_query(['notify' => 'fail']);
            http_response_code(500);
            header("Location: /tournament?$query");
            exit;
        }
        $values = [
            't_name' => $tournament->getName(),
            't_date' => $tournament->getDate(),
            'p_niockname' => $tournament->getPlayers()
        ];


        $this->view->render('tournament_add', ['user' => $this->user, 'values' => $values]);

    }
}