<?php


namespace App\models;


use App\components\TournamentInterface;
use PDO;

class PlayersTDG extends TDG
{
    public function getPlayersbyTournamentID($tournamentID, $ids = null): ?array
    {
        if ($ids) {
            if (is_array($ids)) {
                $ids = array_map(function($ids){return "'{$ids}'";},$ids);
                $ids = '(' . implode(', ', $ids) . ')';
            } else {
                $ids = "({$ids})";
            }
            $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID' AND `id` IN $ids");
        } else {
            $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID'");
        }
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function setTeam(Players $player): bool
    {
        $sql = "UPDATE `$this->table` SET `team` = ? WHERE `id` = {$player->getId()}";
        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(1, $player->getTeam(), PDO::PARAM_STR);
        $stmt->execute();
        return true;
    }

    public function getPlayersbyTeam(TournamentInterface $tournament, $team): ?array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `team` = '$team'");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    /**
     * Возвращает живых игроков, учитывая отстраненных
     * @param TournamentInterface $tournament
     * @return array|null
     */
    public function getAlivePlayers(TournamentInterface $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `lives` > 0");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function getLoosers(TournamentInterface $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `lives` <= 0");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function getWaitingPlayers(TournamentInterface $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `team` = 'WAIT'");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function getPlayersOrderedByLives(TournamentInterface $tournament)
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' ORDER BY `lives` DESC");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    public function resetSuspension(TournamentInterface $tournament)
    {
        $query = $this->connection->query("UPDATE `$this->table` SET `is_suspended` = false WHERE `tournament_id` = {$tournament->getId()}");
        return $query ? true : false;
    }

    public function deleteAllPlayers(TournamentInterface $tournament)
    {
        $query = $this->connection->query("DELETE FROM `$this->table` WHERE `tournament_id` = {$tournament->getId()}");
        return $query ? true : false;
    }

    /**
     * Получает игроков, которые не в ожидающих
     * @param TournamentInterface $tournament
     * @return array
     */
    public function getPlayersInGame(TournamentInterface $tournament)
    {
        $wait = Players::STATUS_WAIT;
        $out = Players::STATUS_OUT;
        $query = $this->connection->query("SELECT * FROM {$this->table} WHERE tournament_id = '{$tournament->getId()}' AND team NOT IN ('$wait', '$out')");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

    /**
     * Получает игроков, которые могут выйти на замену - ждущие, за исключением  отстраненных
     * @param TournamentInterface $tournament
     * @return array
     */
    public function getPossibleChangePlayers(TournamentInterface $tournament)
    {
        $wait = Players::STATUS_WAIT;
        $out = Players::STATUS_OUT;
        $query = $this->connection->query("SELECT * FROM {$this->table} WHERE tournament_id = '{$tournament->getId()}' AND team = '$wait' AND is_suspended != 1");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, '\App\models\Players');
        return $players ? $players : null;
    }

}