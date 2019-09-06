<?php


namespace App\models;


class TournamentTDG extends TDG
{
    public function saveTournament(Tournament $tournament): ?int
    {
        $this->insertValues([
           'name' =>  $tournament->getName(),
            'date' => $tournament->getDate(),
            'owner_id' => $tournament->getOwnerId()
        ]);
        $id = $this->connection->lastInsertId();
        return  $id ? $id : null;
    }

    public function updateTournament(Tournament $tournament):void
    {
        $this->updateValues([
            'name' =>  $tournament->getName(),
            'date' => $tournament->getDate(),
            'owner_id' => $tournament->getOwnerId(),
            'status' =>  $tournament->getStatus(),
            'current_round' => $tournament->getCurrentRound(),
            'round_count' => $tournament->getRoundCount(),
            'toss' => json_encode($tournament->getToss())
        ], $tournament->getId());
    }

    public function getTournamentsByUser(int $ownerID):?array {
        $query = $this->connection->query("SELECT * FROM `tournament` WHERE `owner_id` = '$ownerID'");
        $tournaments = $query->fetchAll(\PDO::FETCH_CLASS, '\App\models\Tournament');
        return $tournaments ? $tournaments : null;
    }

    public function getTournamentById(int $tournamentID):?Tournament {
        $stmt = $this->connection->prepare("SELECT * FROM `tournament` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, \PDO::PARAM_INT);
        $stmt->execute();
        $tournament = $stmt->fetchObject( '\App\models\Tournament');
        return $tournament ? $tournament : null;
    }

    public function deleteTournamentById(string $tournamentID)
    {
        $stmt = $this->connection->prepare("DELETE FROM `$this->table` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, \PDO::PARAM_INT);
        $result = $stmt->execute();
        return $result ? true : false;
    }

}