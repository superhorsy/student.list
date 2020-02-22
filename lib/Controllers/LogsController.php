<?php


namespace App\Controllers;

use App\Components\Controller;
use App\Components\View;

class LogsController extends Controller
{
    public $view;
    public $is_owner;
    private $user;
    private $tdg;
    /**
     * Сообщение из реквеста
     * @var
     */
    private $notification = "";

    function __construct()
    {
        $this->view = new View();

        if (!isset($_COOKIE['auth'])) {
            if (!$this->hasAccess()) {
                header("Location: /index");
                exit;
            }
        }
    }

    public function action()
    {
        $logs = scandir(ROOT . DIRECTORY_SEPARATOR . 'tournament_logs');
        array_shift($logs);
        array_shift($logs);
        $this->view->render('log', ['logs' => $logs]);

    }

    public function actionView($fileName)
    {
        $logs = scandir(ROOT . DIRECTORY_SEPARATOR . 'tournament_logs');
        $file = file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'tournament_logs' . DIRECTORY_SEPARATOR . $fileName);
        $this->view->render('log_view', ['file' => $file]);

    }
}