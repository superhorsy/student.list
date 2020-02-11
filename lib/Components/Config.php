<?php


namespace App\Components;


class Config
{
    private static $_instance = null;

    public static function getInstance()
    {
        if (!self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    private function __construct()
    {
        $config = parse_ini_file(ROOT . '/../config.ini');
        foreach ($config as $key => $value) {
            $this->$key = $value;
        }
    }
}