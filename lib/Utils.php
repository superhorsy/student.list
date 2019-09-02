<?php


namespace App;


use App\models\UserTDG;

class Utils
{

    private static function trimValue($value)
    {
        if(is_array($value)) {
            array_filter($value, 'self::trimValue');
            return $value;
        }

        $value = strval($value);
        return trim($value);
    }

    public static function getUserValues(array $postData): array
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

    public static function getTournamentValues(array $postData, int $ownerId): array
    {
        $values = array_filter($postData, 'self::trimValue');

        return [
            'name' => $values['t_name'] ?? '',
            'date' => $values['t_date'] ?? '',
            'players' => $values['p_nickname'] ?? '',
            'owner_id' => $ownerId,
        ];
    }
}