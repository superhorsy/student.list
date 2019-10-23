<?php


namespace App\models;


class PlayersTDG extends TDG
{
    public function getPlayersbyTournamentID($tournamentID, $ids = null): ?array
    {
        if ($ids) {
            if (is_array($ids)) {
                $ids = array_map(function($ids){return "'" . $ids . "'";},$ids);
                $ids = '(' . implode(', ', $ids) . ')';
            } else {
                $ids = '(' . $ids . ')';
            }
            $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID' AND `id` IN $ids");
        } else {
            $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID'");
        }
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

    public function getPlayersbyTeam(Tournament $tournament, $team): ?array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `team` = '$team'");
        $players = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function getAlivePlayers(Tournament $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `lifes` > 0");
        $players = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function getLoosers(Tournament $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `lifes` <= 0");
        $players = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function getWaitingPlayers(Tournament $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `team` = 'WAIT'");
        $players = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }
    public function getPlayersOrderedByLifes(Tournament $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' ORDER BY `lifes` DESC");
        $players = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function resetSuspension(Tournament $tournament)
    {
        $query = $this->connection->query("UPDATE `$this->table` SET `is_suspended` = false WHERE `tournament_id` = {$tournament->getId()}");
        return $query ? true : false;
    }

    public function deleteAllPlayers(Tournament $tournament)
    {
        $query = $this->connection->query("DELETE FROM `$this->table` WHERE `tournament_id` = {$tournament->getId()}");
        return $query ? true : false;
    }

}