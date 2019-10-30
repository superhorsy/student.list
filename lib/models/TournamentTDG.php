<?php

namespace App\models;

use PDO;

class TournamentTDG extends TDG
{
    public function saveTournament(TournamentInterface $tournament): ?int
    {
        $this->insertValues([
            'name' => $tournament->getName(),
            'date' => $tournament->getDate(),
            'owner_id' => $tournament->getOwnerId(),
            'prize_pool' => $tournament->getPrizePool(),
            'type' => $tournament->getType(),
            'regions' => json_encode($tournament->getRegions(),JSON_UNESCAPED_UNICODE),
        ]);
        $id = $this->connection->lastInsertId();
        return $id ? $id : null;
    }

    public function updateTournament(TournamentInterface $tournament): void
    {
        $this->updateValues([
            'name' => $tournament->getName(),
            'date' => $tournament->getDate(),
            'owner_id' => $tournament->getOwnerId(),
            'status' => $tournament->getStatus(),
            'current_round' => $tournament->getCurrentRound(),
            'round_count' => $tournament->getRoundCount(),
            'toss' => json_encode($tournament->getToss(), JSON_UNESCAPED_UNICODE),
            'prize_pool' => $tournament->getPrizePool(),
            'type' => $tournament->getType(),
            'regions' => json_encode($tournament->getRegions(),JSON_UNESCAPED_UNICODE),
        ], $tournament->getId());
    }

    /**
     * @param int $ownerID
     * @return array|null TournamentInterface[]
     */
    public function getTournamentsByUser(int $ownerID): ?array
    {
        $tournaments = $this->connection->query("SELECT id, type FROM `tournament` WHERE `owner_id` = '$ownerID'")
            ->fetchAll(PDO::FETCH_ASSOC);
        $tournaments_array = array();
        foreach ($tournaments as $tournament) {
            if (in_array((int)$tournament['type'], TournamentFactory::TOURNAMENT_TYPE)) {
                $tournaments_array[] = $this->getObj((int)$tournament['id'], (int)$tournament['type']);
            }
        }
        return $tournaments_array ? $tournaments_array : null;
    }

    public function getTournamentById(int $tournamentID, int $type = null): ?TournamentInterface
    {
        $stmt = $this->connection->prepare("SELECT id, type FROM `tournament` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, PDO::PARAM_INT);
        $stmt->execute();
        $tournament = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$type) {
            if (in_array((int)$tournament[0]['type'], TournamentFactory::TOURNAMENT_TYPE)) {
                $tournament = $this->getObj((int)$tournament[0]['id'], (int)$tournament[0]['type']);
            }
        } else {
            $tournament = $this->getObj((int)$tournament[0]['id'], (int)$type);
        }
        return $tournament ? $tournament : null;
    }

    public function deleteTournamentById(string $tournamentID): bool
    {
        $stmt = $this->connection->prepare("DELETE FROM `{$this->table}` WHERE `id` = ?");
        $stmt->bindValue(1, $tournamentID, PDO::PARAM_INT);
        return $stmt->execute() ? true : false;
    }

    private function getObj($id, $type): TournamentInterface
    {
        return $this->connection->query("SELECT * FROM `tournament` WHERE `id` = {$id}")
            ->fetchObject(TournamentFactory::TOURNAMENT_CLASS[$type]);
    }

}