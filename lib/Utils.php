<?php


namespace App;

class Utils
{

    private static function trimValue($value)
    {
        if (is_array($value)) {
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
            'prize_pool' => $values['t_prize_pool'] ?? '',
            'owner_id' => $ownerId,
            'type' => (int) $values['t_type'],
            'regions' => $values['t_regions'] ? explode(',', $values['t_regions']) : null,
            'players' => $values['players'] ?? '',
        ];
    }

    /**
     * Estimates count of rounds till the end
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