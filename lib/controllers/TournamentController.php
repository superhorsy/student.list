<?php


namespace App\controllers;

use App\{Controller,
    models\exception\TournamentException,
    models\Tournament,
    models\TournamentFactory,
    models\TournamentTDG,
    models\User,
    Utils};

class TournamentController extends Controller
{

    public $view;
    public $is_owner;
    private $user = null;
    private $tdg;

    function __construct()
    {
        $this->view = new \App\View();
        $this->tdg = new TournamentTDG();
        if (!$_SESSION['token_tournament']) {
            $_SESSION['token_tournament'] = md5(uniqid(mt_rand(), true));
        }

        if (!isset($_COOKIE['auth'])) {
            if (!$this->hasAccess()) {
                header("Location: /index");
                exit;
            }
        } else {
            $this->user = new User($_COOKIE['auth']);
        }

        $notify = $_GET['notify'] ? strval($_GET['notify']) : '';

    }

    public function access()
    {
        return [
            self::ACCESS_ALL => ['actionShow']
        ];
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

        $this->is_owner = $this->user && $tournament->getOwnerId() == $this->user->getId() ? true : false;

        if ($tournament) {
            if ($this->is_owner) {
                if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                    if (!$_POST['token_tournament']) {
                        $errors[] = 'Форма отправлена со стороннего сайта.';
                    } else {
                        switch ($_POST['tournament_action']) {
                            case 'start':
                                $tournament->start();
                                break;
                            case 'next':
                                if ($tournament->getStatus() == Tournament::STATUS_IN_PROGRESS) {
                                    $playersPlaying = count($tournament->getPlayers()) - count($tournament->getWaitingPlayers()) - count($tournament->getLoosers());

                                    if (!(isset($_POST['loosers']) && !empty($_POST['loosers']) && is_array($_POST['loosers'])) || count($_POST['loosers']) != ($playersPlaying) / 10) {
                                        $errors[] = 'Отмечены не все проигравшие';
                                    }
                                    if (!$errors) {
                                        $roundResult = [
                                            'loosers' => $_POST['loosers'] ?? '',
                                        ];
                                        $tournament->next($roundResult);
                                    }
                                }
                                break;
                            case 'reset':
                                $tournament->reset();
                                break;
                            case 'send_home':
                                if (isset($_POST['sendHome']) && $_POST['sendHome']) {
                                    $data = [
                                        'sendHome' => $_POST['sendHome'] ?? '',
                                    ];
                                    $tournament->sendHome($data);
                                } else {
                                    $errors[] = 'Не отмечены игроки';
                                }
                                break;
                        }
                    }
                }
                $this->view->render('tournament_show', ['tournament' => $tournament, 'is_owner' => $this->is_owner,
                    'notify' => $notify ?? false, 'errors' => $errors]);
            } else {

                $this->view->render('tournament_show', ['tournament' => $tournament, 'is_owner' => $this->is_owner]);
            }
        } else {
            header("Location: /index");
            exit;
        }
    }

    public function actionAdd(array $errors = null, array $values = null)
    {

        $values = $errors = [];

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!$_POST['token_tournament']) {
                $errors = 'Форма отправлена со стороннего сайта.';
            } else {
                $values = Utils::getTournamentValues($_POST, $this->user->getId());
                try {
                    $tournament = TournamentFactory::factory((int)$_POST['t_type']);
                    $tournament->hydrate($values);
                    $errors = $tournament->isValid();
                } catch (TournamentException $e) {
                    $errors[] = $e->getMessage();
                }
            }
            if (empty($errors)) {
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

        $this->view->render('tournament_add', ['user' => $this->user, 'errors' => $errors, 'tournament' => $tournament]);

    }

    public function actionEdit(int $tournamentId)
    {
        $tournament = $this->tdg->getTournamentById($tournamentId);
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (!$_POST['token_tournament']) {
                $errors = 'Форма отправлена со стороннего сайта.';
            } else {
                $values = Utils::getTournamentValues($_POST, $this->user->getId());
                if (!$tournament || !$tournament->getOwnerId() == $this->user->getId()) {
                    $errors = 'Ошибка доступа к турниру.';
                } else {
                    $tournament->hydrate($values);
                    $errors = $tournament->isValid();
                }
            }
            if (empty($errors)) {
                if (!($tournament->save(2))) {
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

        $this->view->render('tournament_add', ['user' => $this->user, 'errors' => $errors, 'tournament' => $tournament]);

    }
}