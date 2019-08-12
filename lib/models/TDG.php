<?php

namespace App\Models;

use PDO;

abstract class TDG
{

    protected $connection = null;
    protected $table = null;

// Подключение к базе данных
    public function __construct()
    {
        $this->table = $this->getTableName();
        global $db;
        if (!isset($db)) {
            $config = parse_ini_file(ROOT . '/../config.ini');
            $mysql_host = $config['host'];
            $mysql_database = $config['database'];
            $mysql_user = $config['user'];
            $mysql_password = $config['password'];
            $type = $config['type'];
            $dsn = "$type:host=$mysql_host;dbname=$mysql_database;charset=utf8mb4"; // utf8mb4, чтобы создаваемые в ней таблицы поддерживали хранение любых символов Юникода
            $dsn_Options = [
                PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,                                              // выводит ошибки SQL при взаимодействии с базой
                PDO::MYSQL_ATTR_INIT_COMMAND    => "SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';"   //строгий режим MySQL
            ];
            $db = new PDO($dsn, $mysql_user, $mysql_password, $dsn_Options);
        }
        $this->connection = $db;
    }

    protected function getTableName()
    {
        $className = mb_strtolower(get_class($this));
        $tableName = preg_match('~\\\\([a-z]*)tdg$~u', $className,$matches);

        return $matches[1];
    }

    //Подсчет строк
    public function getCount(string $where = ''): int
    {
        $object = $this->connection->query("COUNT * FROM `$this->table` $where");
        $pagesCount = $object->fetch(PDO::FETCH_ASSOC);
        return $pagesCount[0];
    }

    //Подгрузка всех значений из БД
    public function getAll():array
    {
        $dataArray = $this->connection->query("SELECT * FROM `$this->table`")->fetchAll(PDO::FETCH_ASSOC);
        return $dataArray;
    }

    //Insert values with array [column => data]
    public function insertValues(array $values)
    {
        if (!$values) {
            return false;
        }
        //Parameters and bind string
        $columns = array_keys($values);
        $bind = [];
        foreach ($columns as $column) {
            $bind[] = ':' . $column;
        }

        $columnString = "`" . implode("`, `", $columns) . "`";
        $columnBindString = implode(', ',$bind);


        //Creating variable for "Prepare"
        // The SQL query you want to run is entered as the parameter, and placeholders are written like this :placeholder_name

        $myInsertStatment = $this->connection->prepare("INSERT INTO $this->table ($columnString) VALUES ($columnBindString)");

        // Now we tell the script which variable each placeholder actually refers to using the bindParam() method
        // First parameter is the placeholder in the statement above - the second parameter is a variable that it should refer to

        foreach ($values as $column => $value) {
            $columnBindName = ":" . $column;
            $myInsertStatment->bindValue($columnBindName, $value);
        }

        if ($myInsertStatment->execute()) {
            return true;
        }
    }
}
