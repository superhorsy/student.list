<?php

namespace App\Models\Tournament\Interfaces;


/**
 * Class Tournament
 * @package App\Models
 */
interface TournamentInterface
{
    public function start();

    public function next(array $roundResult);

    public function sendHome(array $roundResult);

    public function reset();

    public function getEstimation(): int;

    public function hydrate(array $values);

    public function save(int $mode = 1): bool;

    public function isValid(): ?array;

    public function getPlayers(): array;

    public function setPlayers(array $nicknames = array()): void;

    public function toss();

    public function setId($id): void;

    public function setName($name): void;

    public function setDate($date): void;

    public function setOwnerId($owner_id): void;

    public function getStatus();

    public function setStatus($status): void;

    public function setCurrentRound($current_round): void;

    public function setRoundCount($round_count): void;

    public function setToss($toss): void;

    public function getToss();

    public function getPlayersByTeam($team);

    public function getWaitingPlayers(): array;

    public function getLoosers(): array;

    public function setLoosers($loosers): void;

    public function getPlayersOrderedByLives();

    public function setPrizePool($prize_pool): void;

    public function reward();

    public function setType($type): void;

    public function swapPlayersTeams(array $playerIds);
}