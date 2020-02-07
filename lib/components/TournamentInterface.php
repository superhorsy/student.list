<?php

namespace App\components;


/**
 * Class Tournament
 * @package App\models
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

    public function getId();

    public function setId($id): void;

    public function getName();

   public function  setName($name): void;

    public function getDate();

    public function setDate($date): void;

    public function getOwnerId();

    public function setOwnerId($owner_id): void;

    public function getStatus();

    public function setStatus($status): void;

    public function getCurrentRound();

    public function setCurrentRound($current_round): void;

    public function getRoundCount();

    public function setRoundCount($round_count): void;

    public function setToss($toss): void;

    public function getToss();

    public function getPlayersByTeam($team);

    public function getWaitingPlayers();

    public function getLoosers();

    public function setLoosers($loosers): void;

    public function getPlayersOrderedByLifes();

    public function getPrizePool();

    public function setPrizePool($prize_pool): void;

    public function reward();

    public function getType();

    public function setType($type): void;
}