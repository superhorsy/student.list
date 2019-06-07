<?php


//Функция проверки капчи
require dirname(__DIR__).'/models/verify_captcha.php';
//Функция загрузки и проверки файла
require dirname(__DIR__).'/models/file_upload.php';

//Заполнена ли форма?
if ( isset($_POST['name']) && ($_POST['email']) ) {

//Капча заполнена?

if (empty($_POST['g-recaptcha-response'])) {
echo "Пройдите проверку!<br>";
}

else {

//Запуск проверки капчи
verify();

if ($result = 1) {

echo 'Капча успешно пройдена! <br>';

//Получаем данные из формы

$name = $_POST['name'];
$email = $_POST['email'];
$message = $_POST['message'];
$fileName = $_FILES['file']['name'];

//Загружаем файл
$fileUploadResult = fileUpload();
if ($fileUploadResult === false) {
$fileName = "File is not attached.";
}

//Передаем пользовательские данные в базу данных
$db = new DB();
$db->insert(blog, $name, $email, $message, $fileName);

//Проверяем загружен ли файл?
if ($fileUploadResult === true) {
echo "Файл успешно загружен!";
}
else echo "Файл не загружен!";
}

else {
echo 'Неверная капча!<br>';
}

}
}

else {
echo "Пожалуйста, заполните форму<br>";
}