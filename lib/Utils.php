<?php


namespace App;


use App\models\UserTDG;

class Utils
{
    public static function getValues(array $postData): array
    {
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

        return $values;
    }
}