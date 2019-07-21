<?php

abstract class TableDataGateway
{

    private $connection = null;
    private $table = null;

// Подключение к базе данных
    public function __construct()
    {
        $this->table = $this->getTableName();
        $config = parse_ini_file(__DIR__ . '/config.ini');

        $mysql_host = $config['host'];
        $mysql_database = $config['database'];
        $mysql_user = $config['root'];
        $mysql_password = $config['password'];
        $dsn = "mysql:host=$mysql_host;dbname=$mysql_database;charset=utf8mb4"; // utf8mb4, чтобы создаваемые в ней таблицы поддерживали хранение любых символов Юникода
        $dsn_Options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // выводит ошибки SQL при взаимодействии с базой
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';"]; //строгий режим MySQL
        $dbConnection = new PDO($dsn, $mysql_user, $mysql_password, $dsn_Options);
        $this->connection = $dbConnection;
    }

    private function getTableName()
    {
        $path = explode('DataGateway\\', __CLASS__);
        return array_pop($path);
    }

    //Подсчет строк
    public function getCount()
    {
        $object = $this->connection->query("COUNT * FROM `$this->table`");
        $pagesCount = $object->fetch(PDO::FETCH_ASSOC);
        return $pagesCount[0];
    }

    //Подгрузка всех значений из БД
    public function getAll()
    {
        $rows = $this->connection->query("SELECT * FROM `$this->table`")->fetchAll(PDO::FETCH_ASSOC);
        return $rows;
    }

    //Insert values with array [column => data]
    public function insertValues(array $values)
    {
        if (!$values) {
            return false;
        }
        //Parameters and bind string
        $columns = array();

        foreach ($values as $k => $v) {
            $columns[] = $k;
        }
        $columnsString = '"' . implode(', "', $columns) . '"';
        $columnBindString = ':' . implode(', :', $columns);

        //Creating variable for "Prepare"
        // The SQL query you want to run is entered as the parameter, and placeholders are written like this :placeholder_name

        $myInsertStatment = $this->connection->prepare("
            INSERT INTO `$this->table` ($columnsString) 
            VALUES ($columnBindString)
            ");

        // Now we tell the script which variable each placeholder actually refers to using the bindParam() method
        // First parameter is the placeholder in the statement above - the second parameter is a variable that it should refer to

        foreach ($values as $column => $value) {
            $columnBindName = ":" . $column;
            $myInsertStatment->bindParam($columnBindName, $value);
        }

        if ($myInsertStatment->execute()) {
            return true;
        }
    }
}
