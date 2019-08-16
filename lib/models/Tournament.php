<?php


namespace App\models;


class Tournament
{
    private $tdg;

    private $id = null;
    private $name = null;
    private $datetime = null;
    private $owner_id = null;
    private $players = array();
    private $status;
    private $current_round;
    private $current_toss;

    private $teams;
    private $loosers = array();

    /**
     * @return mixed
     */
    public function getLoosers()
    {
        return $this->loosers;
    }

    /**
     * @param mixed $loosers
     */
    public function setLoosers($loosers): void
    {
        $this->loosers = $loosers;
    }


    private $round_count;

    const STATUS_IN_PROGRESS = 'in progress';

    /**
     * Tournament constructor.
     * @param $tdg
     */
    public function __construct()
    {
        $tdg = new TournamentTDG();
        $this->tdg = $tdg;
        if ($this->id) {
            $this->setPlayers();
        }
    }

    public function start()
    {
        if ($this->status != self::STATUS_IN_PROGRESS) {
            $this->setStatus(self::STATUS_IN_PROGRESS);
        }

        $this->mix();
        $teams = $this->getTeams();
        $this->toss($teams);

        if (!$this->current_round) {
            $this->current_round = 1;
        } else {
            $this->current_round++;
        }

        $round_count = (count($this->players) - (count($this->players)%10))/5;
        $this->round_count = $round_count;

        $this->save();

    }

    public function next(array $roundResult)
    {
        foreach($roundResult['loosers'] as $team) {
            $playerIds = (new PlayersTDG())->getPlayerIdsByTeam($this, $team);
            foreach ($playerIds as $playerId) {
                $this->removeLife($playerId);
            }
        }

        $this->mix();
        $teams = $this->getTeams();
        $this->toss($teams);

        if (!$this->current_round) {
            $this->current_round = 1;
        } else {
            $this->current_round++;
        }

        $round_count = (count($this->players) - (count($this->players)%10))/5;
        $this->round_count = $round_count;

        $this->save();

    }

    public function getTeams():array {
        $teams = array();
        $count = count($this->players);
        $inGame = $count - ($count % 10);
        $inGamePlayers = array_slice($this->players,0,$inGame);
        foreach($inGamePlayers as $player) {
            $team = $player->getTeam();
            if(!array_key_exists($team,$teams)) {
                $teams[$team] = array();
            }
            array_push($teams[$team], $player);
        }

        $waitingPlayers = array_slice($this->players, $inGame);
        if($waitingPlayers) {
            $teams['waiting'] = array();
            array_push($teams['waiting'], $waitingPlayers);
        }

        return $teams;
    }

    public function mix()
    {
        shuffle($this->players);
        $teamArray = array_map('str_getcsv', file(ROOT . '/../Heroes of Dota.csv'));
        $teamName = $teamArray[mt_rand(1,117)][0];
        for ($i = 0; $this->players[$i]; $i++) {
            $player = $this->players[$i];
            $player->setTeam($teamName);
            if (($i+1)%5 == 0) {
                $teamName = $teamArray[mt_rand(1,117)][0];
            }
        }
    }

    public function hydrate(array $values)
    {
        $values['id'] ? $this->setId($values['id']) : $this->setId('');
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['datetime'] ? $this->setDatetime($values['datetime']) : $this->setDatetime('');
        $values['owner_id'] ? $this->setOwnerId($values['owner_id']) : $this->setOwnerId('');
        //Отдельно формируем игроков
        if ($values['players'] && is_array($values['players'])) {
            $this->setPlayers($values['players']);
        }
    }

    public function save(): bool
    {

        if($this->id) {
            $this->tdg->updateTournament($this);
            return true;
        }

        $tournamentId = $this->tdg->saveTournament($this);

        if (!$tournamentId) {
            return false;
        }

        $this->id = $tournamentId;

        foreach ($this->players as $player) {
            $player->setTournamentId($tournamentId);
            $player->save();
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

        if (!$this->datetime) {
            $errors[] = 'Отсутствует дата турнира';
        } else {
            try {
                $datetime = new \DateTime($this->datetime);
            } catch (\Throwable $exception) {
                $errors[] = 'Введена некорректная дата турнира';
            }
            if ($datetime < new \DateTime()) {
                $errors[] = 'Дата турнира не может быть раньше текущей даты';
            }
        }

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
            $players = [];
            foreach ($nicknames as $nickname) {
                $player = new Players();
                $playerData = ['nickname' => $nickname];
                $player->hydrate($playerData);
                $this->players[] = $player;
            }
        }
        if ($this->id) {
            $this->players = (new PlayersTDG())->getPlayersbyTournamentID($this->id);
        }
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
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param null $datetime
     */
    public function setDatetime($datetime): void
    {
        $this->datetime = $datetime;
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
     * @return mixed
     */
    public function toss($teams)
    {
        {
            $toss = array();

            if (array_key_exists('waiting', $teams)) {
                $waiting = $teams['waiting'];
                unset($teams['waiting']);
            }

            $groupNumber = 'A';
            $toss[$groupNumber] = array();
            $group = array();
            foreach ($teams as $teamName => $team) {
                if (count($group) < 2) {
                    $group[$teamName] = $team;
                } else {
                    $toss[$groupNumber] = $group;
                    $group = array();
                    $groupNumber++;
                    $group[$teamName] = $team;
                }
            }
            $toss[$groupNumber] = $group;

            if(isset($waiting)) {
                $toss['waiting'] = $waiting;
            }

            $this->current_toss = $toss;

            return $this->current_toss;
        }
    }

    /**
     * @param mixed $current_toss
     */
    public function setCurrentToss($current_toss): void
    {
        $this->current_toss = $current_toss;
    }

    /**
     * @return mixed
     */
    public function getCurrentToss()
    {
        return $this->current_toss;
    }

    private function removeLife($Id)
    {
        foreach($this->players as $key => $player) {
            if($player->getId() == $Id) {
                $playerLifes = $player->getLifes();
                $player->setLifes(--$playerLifes);
                $player->save();
                if ($player->getLifes() == 0) {
                    $this->loosers[] = $player;
                    unset($this->players[$key]);
                }
            }
        }
    }
}