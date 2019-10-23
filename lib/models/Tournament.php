<?php


namespace App\models;


use App\Utils;

class Tournament
{
    private $tdg;

    private $id = null;
    private $name = null;
    private $date = null;
    private $owner_id = null;
    private $status;
    private $current_round;
    private $round_count;
    private $toss;
    private $prize_pool;

    private $players = array();
    private $loosers = array();

    /*Статусы турнира*/
    const STATUS_AWAITING = 'awaiting';
    const STATUS_IN_PROGRESS = 'in progress';
    const STATUS_ENDED = 'ended';

    /**
     * Tournament constructor.
     * @param $tdg
     */
    public function __construct()
    {
        $this->tdg = new TournamentTDG();
        if ($this->id) {
            $this->setPlayers();
        }
        if ($this->toss) {
            $this->toss = json_decode($this->toss);
        }
    }

    public function start()
    {
        if ($this->status != self::STATUS_IN_PROGRESS) {
            $this->setStatus(self::STATUS_IN_PROGRESS);


            $teams = $this->setTeams();
            $this->toss($teams);

            $this->current_round = 1;

            $round_count = (count($this->players) - (count($this->players) % 10)) / 5;
            $this->round_count = $round_count;

            $this->save();

        }

    }

    public function next(array $roundResult)
    {
        if ($this->status == self::STATUS_IN_PROGRESS) {

            (new PlayersTDG())->resetSuspension($this);

            foreach ($roundResult['loosers'] as $team) {
                $loosers = (new PlayersTDG())->getPlayersByTeam($this, $team);
                foreach ($loosers as $looser) {
                    $lifes = $looser->getLifes();
                    $looser->setLifes(--$lifes);
                    if ($looser->getLifes() == 0) {
                        $looser->setTeam('OUT');
                    }
                    $looser->save();
                }
            }
            $this->setPlayers();

            $alivePlayers = $this->getAlivePlayers();

            if (count($alivePlayers) >= 10) {
                $teams = $this->setTeams();
                $this->toss($teams);
                $this->current_round++;
            } else {
                $this->setStatus(self::STATUS_ENDED);
            }

            $this->save();
        }
    }

    public function sendHome(array $roundResult)
    {
        $waitingPlayers = $this->getWaitingPlayers();

        foreach ($roundResult['sendHome'] as $id => $action) {
            $player = (new PlayersTDG())->getPlayersbyTournamentID($this->id, $id)[0];
            if ($waitingPlayers) {
                if (isset($join)) {
                    $join = next($waitingPlayers);
                } else {
                    $join = current($waitingPlayers);
                }
            }
            switch ($action) {
                case '1': //Убрать с посева
                    if ($join) {
                        $join->setTeam($player->getTeam());
                    }
                    $player->setTeam(Players::STATUS_WAIT);
                    $player->setIsSuspended(true);
                    break;
                case '2': //Убрать жизнь и снять с посева
                    if ($join) {
                        $join->setTeam($player->getTeam());
                    }
                    $player->setLifes($player->getLifes() - 1);
                    if ($player->getLifes() < 1) {
                        $player->setTeam(Players::STATUS_OUT);
                    } else {
                        $player->setTeam(Players::STATUS_WAIT);
                    }
                    $player->setIsSuspended(true);
                    break;
                case '3': //Дисквалифицировать
                    if ($join) {
                        $join->setTeam($player->getTeam());
                    }
                    $player->setLifes(0);
                    $player->setTeam(Players::STATUS_OUT);
                    $player->setIsSuspended(true);
                    break;
                default:
                    continue 2;
            }
            $player->save();
            if ($join) {
                $join->save();
            }
        }

        $teams = $this->setTeams();
        $this->toss($teams);

        $this->save();
    }

    public function reset()
    {
        $this->toss = null;
        $this->current_round = null;
        $this->round_count = null;
        $this->status = self::STATUS_AWAITING;
        foreach ($this->players as $player) {
            $player->setLifes(2);
            $player->setTeam(null);
            $player->setIsSuspended(0);
            $player->save();
        }

        $this->save();
    }

    public function setTeams()
    {

        $players = array();
        foreach ($this->getAlivePlayers() as $player) {
            if (!$player->getIsSuspended()) {
                $players[] = $player;
            } else {
                $player->setTeam(Players::STATUS_WAIT);
                $player->save();
            }
        }

        $waitingPlayers = array();
        foreach ($this->getWaitingPlayers() as $player) {
            if (!$player->getIsSuspended()) {
                $waitingPlayers[] = $player;
            }
        }

        if (!empty($waitingPlayers)) {
            $otherPlayers = array_udiff($players, $waitingPlayers, function ($p1, $p2) {
                return $p1->getId() - $p2->getId();
            });
            shuffle($otherPlayers);
            $players = array_merge($waitingPlayers, $otherPlayers);
        } else {
            shuffle($players);
        }

        //Team names
        $teamNames = array_map('str_getcsv', file(ROOT . '/../Heroes of Dota.csv'));
        $teamNames = array_column($teamNames, 0);

        $teamKeys = array_rand($teamNames, intval(count($players) / 10) * 2);

        foreach ($teamKeys as $key) {
            for ($i = 0; $i < 5; $i++) {
                $player = current($players);
                $player->setTeam($teamNames[$key]);
                $player->save();
                $nextPlayer = next($players);
            }
        }

        $lastPlayer = current(array_slice($players, -1));

        if ($nextPlayer && $nextPlayer == $lastPlayer) {
            $player = current($players);
            $player->setTeam('WAIT');
            $player->save();
        } else {
            while ($nextPlayer) {
                $player = current($players);
                $player->setTeam('WAIT');
                $player->save();
                $nextPlayer = next($players);
            }
        }

        $teams = array_intersect_key($teamNames, array_flip($teamKeys));

        $this->setPlayers();

        return $teams;
    }

    public function getEstimation(): int
    {
        foreach ($this->players as $player) {
            $players[] = $player->getLifes(); //each player has 2 lifes at the start
        }

        $cycles = 15;

        for ($i = 1; $i <= $cycles; $i++) {
            $estimation[] = Utils::estimateRounds($players);
        }

        return intdiv(array_sum($estimation), $cycles);
    }

    public function hydrate(array $values)
    {
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['date'] ? $this->setDate($values['date']) : $this->setDate('');
        $values['prize_pool'] ? $this->setPrizePool($values['prize_pool']) : $this->setPrizePool(null);
        $values['owner_id'] ? $this->setOwnerId($values['owner_id']) : $this->setOwnerId('');
        if(isset($values['players']) && $values['players']) {
            $this->setPlayers($values['players']);
        };
    }

    public function save(int $mode = 1): bool
    {
        if ($this->id) {
            $this->tdg->updateTournament($this);
        } else {
            $tournamentId = $this->tdg->saveTournament($this);
            if (!$tournamentId) {
                return false;
            }
            $this->id = $tournamentId;
        }

        switch ($mode) {
            case 1: // стандартное сохранение/удаление
                foreach ($this->players as $player) {
                    $player->setTournamentId($this->id);
                    $player->save();
                };
                break;
            case 2: //редактирование турнира с удалением игроков
                (new PlayersTDG())->deleteAllPlayers($this);
                foreach ($this->players as $player) {
                    $player->setTournamentId($this->id);
                    $player->save();
                };
                break;
        }

        return $this->id ? true : false;
    }

    public function isValid(): ?array
    {
        $errors = [];

        if (!$this->name) {
            $errors[] = 'Не введено наименование турнира';
        } elseif (strlen($this->name) > 255) {
            $errors[] = 'Наименование турнира не должно превышать 255 символов';
        }

        if (!$this->date) {
            $errors[] = 'Отсутствует дата турнира';
        } else {
            try {
                $date = new \DateTime($this->date);
            } catch (\Throwable $exception) {
                $errors[] = 'Введена некорректная дата турнира';
            }
            if ($date < new \DateTime()) {
                $errors[] = 'Дата турнира не может быть раньше текущей даты';
            }
        }

        if (!$this->players) {
            $errors[] = 'Отсутствуют имена игроков';
        } else {
            $playerNicknames = array();
            foreach ($this->players as $player) {
                $playerNicknames[] = $player->getNickname();
                $playerErrors = $player->isValid();
                if ($playerErrors) {
                    $errors = array_merge($errors, $playerErrors);
                };
            }
            if (count($playerNicknames) !== count(array_unique($playerNicknames))) {
                $errors[] = 'Никнэймы игроков не могут дублироваться';
            }
        }

        return $errors ? $errors : null;
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param array $data
     */
    public function setPlayers(array $nicknames = array()): void
    {
        if ($nicknames) {
            $this->players = array();
            foreach ($nicknames as $nickname) {
                $this->players[] = new Players(['nickname' => $nickname]);
            }
        } elseif ($this->id) {
            $this->players = (new PlayersTDG())->getPlayersbyTournamentID($this->id);
        }
    }

    public function toss($teams)
    {
        $toss = array();

        $groupNumber = 'A';
        $toss[$groupNumber] = array();
        $group = array();
        foreach ($teams as $teamName) {
            if (count($group) < 2) {
                $group[] = $teamName;
            } else {
                $toss[$groupNumber] = $group;
                $group = array();
                $groupNumber++;
                $group[] = $teamName;
            }
        }
        $toss[$groupNumber] = $group;

        $this->toss = $toss;

        return $toss;
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return null
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param null $date
     */
    public function setDate($date): void
    {
        $this->date = $date;
    }

    /**
     * @return null
     */
    public function getOwnerId()
    {
        return $this->owner_id;
    }

    /**
     * @param null $owner_id
     */
    public function setOwnerId($owner_id): void
    {
        $this->owner_id = $owner_id;
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

    /**
     * @return mixed
     */
    public function getCurrentRound()
    {
        return $this->current_round;
    }

    /**
     * @param mixed $current_round
     */
    public function setCurrentRound($current_round): void
    {
        $this->current_round = $current_round;
    }

    /**
     * @return mixed
     */
    public function getRoundCount()
    {
        return $this->round_count;
    }

    /**
     * @param mixed $round_count
     */
    public function setRoundCount($round_count): void
    {
        $this->round_count = $round_count;
    }

    /**
     * @param mixed $toss
     */
    public function setToss($toss): void
    {
        $this->toss = $toss;
    }

    /**
     * @return mixed
     */
    public function getToss()
    {
        return $this->toss;
    }

    public function getPlayersByTeam($team)
    {
        return (new PlayersTDG())->getPlayersbyTeam($this, $team);
    }

    public function getWaitingPlayers()
    {
        return (new PlayersTDG())->getWaitingPlayers($this);
    }

    /**
     * @return mixed
     */
    public function getLoosers()
    {
        return (new PlayersTDG())->getLoosers($this);
    }

    /**
     * @param mixed $loosers
     */
    public function setLoosers($loosers): void
    {
        $this->loosers = $loosers;
    }

    private function getAlivePlayers()
    {
        return (new PlayersTDG())->getAlivePlayers($this);
    }

    public function getPlayersOrderedByLifes()
    {
        return (new PlayersTDG())->getPlayersOrderedByLifes($this);
    }

    /**
     * @return mixed
     */
    public function getPrizePool()
    {
        return $this->prize_pool;
    }

    /**
     * @param mixed $prize_pool
     */
    public function setPrizePool($prize_pool): void
    {
        $this->prize_pool = $prize_pool;
    }

}