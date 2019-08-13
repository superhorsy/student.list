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
}