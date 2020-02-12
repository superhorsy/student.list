<?php


namespace App\Models\Player;


use App\Components\TDG;
use App\Models\Tournament\nterfaces\TournamentInterface;
use PDO;

class PlayersTDG extends TDG
{
    public function getPlayerbyID(int $id): ?Players
    {
        return $this->getObj($id, Players::class) ?? null;
    }

    public function getPlayersbyTournamentID($tournamentID, $ids = null): ?array
    {
        if ($ids) {
            if (is_array($ids)) {
                $ids = array_map(function ($ids) {
                    return "'{$ids}'";
                }, $ids);
                $ids = '(' . implode(', ', $ids) . ')';
            } else {
                $ids = "({$ids})";
            }
            $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID' AND `id` IN $ids  ORDER BY region, wins");
        } else {
            $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '$tournamentID' ORDER BY region, wins");
        }
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ? $players : null;
    }

    public function getPlayersbyTeam(TournamentInterface $tournament, $team): ?array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `team` = '$team'");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ? $players : null;
    }

    /**
     * Возвращает живых игроков, учитывая отстраненных
     * @param TournamentInterface $tournament
     * @return array|null
     */
    public function getAlivePlayers(TournamentInterface $tournament): array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `lives` > 0");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ?? [];
    }

    public function getLoosers(TournamentInterface $tournament): array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `lives` <= 0");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ?? [];
    }

    public function getWaitingPlayers(TournamentInterface $tournament): array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' AND `team` = 'WAIT'  ORDER BY region, wins");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ?? [];
    }

    public function getPlayersOrderedByLives(TournamentInterface $tournament): array
    {
        $query = $this->connection->query("SELECT * FROM `$this->table` WHERE `tournament_id` = '{$tournament->getId()}' ORDER BY lives DESC, `wins` DESC, games_played DESC");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ?? [];
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
    public function getPlayersInGame(TournamentInterface $tournament): array
    {
        $wait = Players::STATUS_WAIT;
        $out = Players::STATUS_OUT;
        $query = $this->connection->query("SELECT * FROM {$this->table} WHERE tournament_id = '{$tournament->getId()}' AND team NOT IN ('$wait', '$out')");
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ?? [];
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
        $players = $query->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, Players::class);
        return $players ? $players : null;
    }
}