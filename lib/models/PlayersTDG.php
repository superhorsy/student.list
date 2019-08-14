<?php


namespace App\models;


class PlayersTDG extends TDG
{
    public function getPlayersbyTournamentID($tournamentID):?array {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID'");
        $players = $query->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function setGroup(Players $player):bool
    {
        $sql = "UPDATE `players` SET `group` = {$player->getGroup()} WHERE `id` = {$player->getId()}";
        if($this->connection->exec($sql)) {
            return true;
        }
        return false;
    }
}