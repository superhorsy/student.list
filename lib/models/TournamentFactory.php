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
        self::TOURNAMENT_TYPE['NORMAL'] => '\App\models\Tournament',
        self::TOURNAMENT_TYPE['INTERREGIONAL'] => '\App\models\TournamentInterregional',

    );

    public static function factory($type) {
        switch ($type) {
            case self::TOURNAMENT_TYPE['NORMAL']:
                return new Tournament();
                break;
            case self::TOURNAMENT_TYPE['INTERREGIONAL']:
                return new TournamentInterregional();
        }
        throw new TournamentException("Не удалось создать турнир типа '$type'");
    }
}