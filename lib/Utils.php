<?php


namespace App;


class Utils
{
    public static function getRegistrationValues(array $postData): array {
        $values = [];
        $data = [];
        foreach ($postData as $key => $value) {
            $value = strval($value);
            $data[$key] = trim($value);
        }
        $values['username'] = isset($data['username']) ? strval($data['username']) : '';
        $values['name'] = isset($data['name']) ? strval($data['name']) : '';
        $values['email'] = isset($data['email']) ? strval($data['email']) : '';
        $values['password'] = isset($data['password']) ? strval($data['password']) : '';
        $values['passwordAgain'] = isset($data['passwordAgain']) ? strval($data['passwordAgain']) : '';
        $values['rememberme'] = isset($data['rememberme']) ? strval($data['rememberme']) : '';

        return $values;
    }

    public static function validateValues(array $values):array {

        $errors = array();

        if (! (filter_has_var(INPUT_POST, 'username') &&
            (strlen(filter_input(INPUT_POST, 'username')) > 0))) {
            $errors[] = 'Отсутствует поле "Имя пользователя"';
        };

        if (! (filter_has_var(INPUT_POST, 'name') &&
            (strlen(filter_input(INPUT_POST, 'name')) > 0))) {
            $errors[] = 'Отсутствует поле "Имя"';
        }

        if (! (filter_has_var(INPUT_POST, 'name') &&
            (strlen(filter_input(INPUT_POST, 'name')) > 0))) {
            $errors[] = 'Отсутствует поле "Имя"';
        }

        return $errors;
}
}