<?php

function verify() {

	if (isset($_POST['g-recaptcha-response'])) {
		$captcha_response = $_POST['g-recaptcha-response'];
	}
	else die('Капча отсутствует');

	//Задаем параметры запроса к API

	$url = 'https://www.google.com/recaptcha/api/siteverify';

	$params = [
	    'secret' => '6Lef0J0UAAAAAJzeP0C6sA6aJfVV_-8jZai5TKkV',
	    'response' => $captcha_response,
	    'remoteip' => $_SERVER['REMOTE_ADDR']
	];

	//С помощью бибилиотеки curl обращаемся к API

	$ch = curl_init ($url);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	 
	$response = curl_exec($ch);

	if(!empty($response)) {
		$decoded_response = json_decode($response);
	}
		
	$success = false;

	if ($decoded_response && $decoded_response->success)
	{
	    $success = $decoded_response->success;
	}

}

?>