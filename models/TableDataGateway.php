<?php

class TableDataGateway {

	public $connection = null;

// Подключение к базе данных
	public function __construct() {

		$mysql_host = "localhost";
		$mysql_database = "blog";
		$mysql_user = "root";
		$mysql_password = "";
		$dsn = "mysql:host=$mysql_host;dbname=$mysql_database;charset=utf8mb4"; // utf8mb4, чтобы создаваемые в ней таблицы поддерживали хранение любых символов Юникода
		$dsn_Options = [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // выводит ошибки SQL при взаимодействии с базой
					PDO::MYSQL_ATTR_INIT_COMMAND => "SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE';"]; //строгий режим MySQL
		$dbConnection = new PDO($dsn, $mysql_user, $mysql_password, $dsn_Options);
		$this->connection = $dbConnection;
	}
//Подсчет строк
	public function count() {

		$dbConnection = $this->connection;
		
		$object = $dbConnection->query($sql);
		$pagesCount = $object->fetch(PDO::FETCH_BOTH);
		$pagesCount = $pagesCount[0];
		return $pagesCount;
	}
//Подгрузка всех значений из БД
	public function all($sql) { 
		$dbConnection = $this->connection;
		$rows = $dbConnection->query($sql);
		return $rows;
	}	
//Внесение строк
	public function insert($tableName, $name, $email, $message, $fileName) { 
		$dbConnection = $this->connection;
		
		//CREATIN VARIABLE FOR "PREPARE"
		// The SQL query you want to run is entered as the parameter, and placeholders are written like this :placeholder_name

		$myInsertStatment = $dbConnection->prepare("INSERT INTO $tableName (name, email, message, file) VALUES (:name, :email, :message, :filename)"); 

		// Now we tell the script which variable each placeholder actually refers to using the bindParam() method
		// First parameter is the placeholder in the statement above - the second parameter is a variable that it should refer to

		$myInsertStatment->bindParam(":name", $name);
		$myInsertStatment->bindParam(":email", $email);
		$myInsertStatment->bindParam(":message", $message);
		$myInsertStatment->bindParam(":filename", $fileName);

		if ($myInsertStatment->execute()) {
				echo "Данные успешно внесены!<br/>";
				}

	}

	//Направление запроса к ДБ
	public function query($sql, array $bind = NULL) { 

		$dbConnection = $this->connection;//подключаемчся к БД

		$myInsertStatment = $dbConnection->prepare($sql);//подготовка запроса

		if (!empty($bind)) {
			foreach ($bind as $key => $value) {
				$myInsertStatment->bindParam($key, $value);// извлекаем и привязываем переменные
			}
		}
		
		if ($myInsertStatment->execute()) { //исполняем запрос
			echo "Запрос успешно выполнен!<br>";
		}

		if (strpos($sql, 'SELECT') !== NULL) {
			return $data = $myInsertStatment->fetchAll();
		}
	}

}

?>
