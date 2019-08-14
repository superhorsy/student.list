<?php


namespace App\models;


class TournamentTDG extends TDG
{
    public function saveTournament(Tournament $tournament): ?int
    {
        $this->insertValues([
           'name'=>  $tournament->getName(),
            'datetime' => $tournament->getDatetime(),
            'owner_id' => $tournament->getOwnerId()
        ]);
        $id = $this->connection->lastInsertId();
        return  $id ? $id : null;
    }

    public function getTournamentsByUser(int $ownerID):?array {
        $query = $this->connection->query("SELECT * FROM `tournament` WHERE `owner_id` = '$ownerID'");
        $tournaments = $query->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, '\App\models\Tournament');
        return $tournaments ? $tournaments : null;
    }

    public function getTournamentById(int $tournamentID):?Tournament {
        $stmt = $this->connection->prepare("SELECT * FROM `tournament` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, \PDO::PARAM_INT);
        $stmt->execute();
        $tournament = $stmt->fetchObject( '\App\models\Tournament');
        return $tournament ? $tournament : null;
    }

}