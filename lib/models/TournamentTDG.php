<?php


namespace App\models;


class TournamentTDG extends TDG
{
    public function saveTournament(Tournament $tournament):?int {
       $stmt = $this->connection->prepare("INSERT INTO `{$this->table}` ('name', 'datetime', 'owner_id') VALUES :name, :datetime, :owner_id");
       $stmt->bindValue(':name', $tournament->getName());

    }
}