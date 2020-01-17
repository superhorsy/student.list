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

    public function setTeams();

    public function getEstimation(): int;

    public function hydrate(array $values);

    public function save(int $mode = 1): bool;

    public function isValid(): ?array;

    /**
     * @return array
     */
    public function getPlayers(): array;

    /**
     * @param array $data
     */
    public function setPlayers(array $nicknames = array()): void;

    public function toss($teams);

    /**
     * @return null
     */
    public function getId();

    /**
     * @param null $id
     */
    public function setId($id): void;

    /**
     * @return null
     */
    public function getName();

    /**
     * @param null $name
     */
    public function setName($name): void;

    /**
     * @return null
     */
    public function getDate();

    /**
     * @param null $date
     */
    public function setDate($date): void;

    /**
     * @return null
     */
    public function getOwnerId();

    /**
     * @param null $owner_id
     */
    public function setOwnerId($owner_id): void;

    /**
     * @return mixed
     */
    public function getStatus();

    /**
     * @param mixed $status
     */
    public function setStatus($status): void;

    /**
     * @return mixed
     */
    public function getCurrentRound();

    /**
     * @param mixed $current_round
     */
    public function setCurrentRound($current_round): void;

    /**
     * @return mixed
     */
    public function getRoundCount();

    /**
     * @param mixed $round_count
     */
    public function setRoundCount($round_count): void;

    /**
     * @param mixed $toss
     */
    public function setToss($toss): void;

    /**
     * @return mixed
     */
    public function getToss();

    public function getPlayersByTeam($team);

    public function getWaitingPlayers();

    /**
     * @return mixed
     */
    public function getLoosers();

    /**
     * @param mixed $loosers
     */
    public function setLoosers($loosers): void;

    public function getPlayersOrderedByLifes();

    /**
     * @return mixed
     */
    public function getPrizePool();

    /**
     * @param mixed $prize_pool
     */
    public function setPrizePool($prize_pool): void;

    public function reward();

    /**
     * @return mixed
     */
    public function getType();

    /**
     * @param mixed $type
     */
    public function setType($type): void;
}