<?php

error_reporting(0);
header('Content-Type: text/html; charset=utf-8');

function fileUpload() {

	if (isset($_FILES['file'])) {
		$errors = array();
		$fileName = $_FILES['file']['name'];
		$fileName = mb_convert_encoding($fileName, 'UTF-8');

		$fileSize = $_FILES['file']['size'];
		$fileTmp = $_FILES['file']['tmp_name'];
		$fileType = $_FILES['file']['type'];
		$fileExt= strtolower(end(explode('.',$_FILES['file']['name'])));
		$fileErrors = $_FILES['file']['error'];
	}

	$extensions = array("jpeg", "png", "jpg", "zip", "rar");

	if(in_array($fileExt, $extensions)=== false) {
		$errors[] = "Файл с таким типом не может быть загружен, допустимые типы - jpeg, png, jpg zip, rar.";
	}

	if(($fileSize > 31457280) or ($fileErrors == 2)) {
		$errors[] = "Файл превышает максимально допустимый размер 30 Мб.";
	}

	if(empty($errors)==true){
		move_uploaded_file($fileTmp, dirname(__FILE__).'/files/'.$fileName);
		return true;
	} else {
		foreach ($errors as $error) {
			echo "Ошибка - $error <br>";
		}
		return false;
	}

}

?>
