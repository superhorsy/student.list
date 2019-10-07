<?php


namespace App;


class Utils
{

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

    /** Estimates count of rounds till the end
     * @param array $players with amount of lifes
     * @return int
     */
    public static function estimateRounds(array $players): int
    {
        $p = array_filter($players);

        for ($round = 0; count($p) >= 10; $round++) {
            shuffle($p);
            $out = array_splice($p, 0, (count($p) - count($p) % 10) / 2);
            array_walk($out, function (&$e) {
                $e--;
            });
            $p = array_merge($p, array_filter($out));
        }
        return $round;
    }
}