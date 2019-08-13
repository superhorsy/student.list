<?php


namespace App;


class ErrorHandler
{
    public function run()
    {
        set_error_handler([$this, 'errorHandler']);
    }

    public function errorHandler($errno, $errstr, $file, $line)
    {
        $this->showError($errno, $errstr, $file, $line, $status = '500');
    }

    private function showError($errno, $errstr, $file, $line, $status = '500')
    {
        $message = "Номер ошибки " . $errno . ': ' . $errstr . $file . $line .  PHP_EOL;
        $config = parse_ini_file(ROOT . '/../config.ini');
        $pathToLog = $config['log'];
        error_log($message, 3, $pathToLog);

        header("HTTP/1.1 {$status}");
        header("Content-type: text/plain; charset=utf-8");
        echo "Извините, на сайте произошла ошибка.\n";
        echo "Попробуйте перезагрузить страницу.\n";
        exit;
    }
}