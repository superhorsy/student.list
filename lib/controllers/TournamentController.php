<?php


namespace App\Controllers;

use App\{components\exceptions\TournamentException,
    Controller,
    models\Tournament,
    models\TournamentFactory,
    models\TournamentTDG,
    models\User,
    Utils,
    View
};

class TournamentController extends Controller
{

    public $view;
    public $is_owner;
    private $user = null;
    private $tdg;
    /**
     * Сообщение из реквеста
     * @var
     */
    private $notification = "";

    function __construct()
    {
        $this->view = new View();
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

        $this->notification = $_GET['notify'] ?? strval($_GET['notify']);

    }

    public function access()
    {
        return [
            static::ACCESS_ALL => ['actionShow']
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
        $this->view->render('tournament', ['user' => $this->user, 'notify' => $this->notification, 'tournaments' => $tournaments]);

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
                                Utils::log($tournament);
                                break;
                            case 'next':
                                if ($tournament->getStatus() == Tournament::STATUS_IN_PROGRESS) {
                                    $playersPlaying = count($tournament->getPlayers()) - count($tournament->getWaitingPlayers()) - count($tournament->getLoosers());

                                    /*if (!(isset($_POST['winners']) && !empty($_POST['winners']) && is_array($_POST['winners'])) || count($_POST['winners']) != ($playersPlaying) / 10) {
                                        $errors[] = 'Отмечены не все победители';
                                    }*/
                                    if (!$errors) {
                                        $roundResult = [
                                            'winners' => $_POST['winners'] ?? '',
                                        ];
                                        $tournament->next($roundResult);
                                        Utils::log($tournament);
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
                            case 'send_home_without_toss':
                                if (isset($_POST['sendHome']) && $_POST['sendHome']) {
                                    $data = [
                                        'sendHome' => $_POST['sendHome'] ?? '',
                                    ];
                                    $tournament->sendHomeWithoutToss($data);
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
                    Utils::responseFail();
                }
                Utils::responseSuccess();
            }
        }

        $this->view->render('tournament_add', ['user' => $this->user, 'errors' => $errors, 'tournament' => $tournament]);

    }

    public function actionEdit(int $tournamentId)
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $tournament = $this->tdg->getTournamentById($tournamentId, (int)$_POST['t_type']);
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
                    Utils::responseFail();
                }
                Utils::responseSuccess();
            }
        } else {
            $tournament = $this->tdg->getTournamentById($tournamentId);
        }

        $this->view->render('tournament_add', ['user' => $this->user, 'errors' => $errors, 'tournament' => $tournament]);

    }
}