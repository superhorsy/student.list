<?php


namespace App\models;


use App\components\exceptions\TournamentException;

class TournamentFactory
{
    const TOURNAMENT_TYPE = array(
        'NORMAL' => 1,
        'INTERREGIONAL' => 2,
    );
    const TOURNAMENT_CLASS = array(
        self::TOURNAMENT_TYPE['NORMAL'] => Tournament::class,
        self::TOURNAMENT_TYPE['INTERREGIONAL'] => TournamentInterregional::class,

    );

    /**
     * Creates tournaments
     * @param $type
     * @return mixed
     * @throws TournamentException
     */
    public static function factory($type) {
        if (isset(self::TOURNAMENT_CLASS[$type])) {
            $className = self::TOURNAMENT_CLASS[$type];
            return new $className();
        }
        throw new TournamentException("Не удалось создать турнир типа '$type'");
    }
}