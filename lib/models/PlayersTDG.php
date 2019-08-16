<?php


namespace App\models;


class PlayersTDG extends TDG
{
    public function getPlayersbyTournamentID($tournamentID): ?array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID'");
        $players = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function setTeam(Players $player): bool
    {
        $sql = "UPDATE `$this->table` SET `team` = ? WHERE `id` = {$player->getId()}";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $player->getTeam(),\PDO::PARAM_STR);
        $stmt->execute();
        return true;
    }

    public function getPlayerIdsByTeam(Tournament $tournament, $team)
    {
        $sql = "SELECT `id` FROM `$this->table` WHERE `tournament_id` = ? AND `team` = ?";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $tournament->getId(),\PDO::PARAM_INT);
        $stmt->bindValue(2, $team,\PDO::PARAM_STR);
        $stmt->execute();
        $playerIds = $stmt->fetchAll(\PDO::FETCH_COLUMN, 0);
        return $playerIds;
    }


}