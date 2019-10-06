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
        $tdg = new TournamentTDG();
        $this->tdg = $tdg;
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
        $goingHomePlayers = (new PlayersTDG())->getPlayersById($this, $roundResult['sendHome']);
        $waitingPlayers = $this->getWaitingPlayers();

        if (count($waitingPlayers) >= count($goingHomePlayers)) {
            $joiningPlayers = array_slice($waitingPlayers, 0, count($goingHomePlayers));
            array_map(function ($goingHome, $joining) {
                $goingHome->setLifes(0);

                $team = $goingHome->getTeam();
                $joining->setTeam($team);
                $goingHome->setTeam('OUT');

                $joining->save();
                $goingHome->save();

            }, $goingHomePlayers, $joiningPlayers);
        } else {
            foreach ($goingHomePlayers as $goingHome) {
                $goingHome->setLifes(0);
                $goingHome->setTeam('OUT');
                $goingHome->save();
            }
            $teams = $this->setTeams();
            $this->toss($teams);
        }

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
            $player->save();
        }

        $this->save();
    }

    public function setTeams()
    {

        $players = $this->getAlivePlayers();

        $waitingPlayers = $this->getWaitingPlayers();

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
        $values['id'] ? $this->setId($values['id']) : $this->setId('');
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['date'] ? $this->setDate($values['date']) : $this->setDate('');
        $values['owner_id'] ? $this->setOwnerId($values['owner_id']) : $this->setOwnerId('');
        //Отдельно формируем игроков
        if ($values['players'] && is_array($values['players'])) {
            $this->setPlayers($values['players']);
        }
    }

    public function save(): bool
    {

        if ($this->id) {
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

}