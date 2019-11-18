<?php


namespace App\models;


use App\Utils;
use DateTime;
use Exception;
use Throwable;

/**
 * Class Tournament
 * @package App\models
 */
class Tournament implements TournamentInterface
{
    protected $tdg;

    protected $id = null;
    protected $name = null;
    protected $date = null;
    protected $owner_id = null;
    protected $status;
    protected $current_round;
    protected $round_count;
    protected $toss;
    protected $prize_pool;
    protected $type;

    protected $players = array();
    protected $loosers = array();

    /*Статусы турнира*/
    const STATUS_AWAITING = 'awaiting';
    const STATUS_IN_PROGRESS = 'in progress';
    const STATUS_ENDED = 'ended';

    /**
     *
     * @param $tdg
     */
    public function __construct()
    {
        $this->tdg = new TournamentTDG();
        if ($this->id) {
            $this->setPlayers();
        }
        if ($this->toss) {
            $this->toss = json_decode($this->toss,true);
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

    /**
     * Подводим результаты раунда
     * @param array $roundResult
     */
    public function next(array $roundResult)
    {
        if ($this->status == self::STATUS_IN_PROGRESS) {

            (new PlayersTDG())->resetSuspension($this);

            /** @var Players $player */
            foreach ($this->players as $player) {
                $team = $player->getTeam();
                $lifes = $player->getLifes();
                $wins = $player->getWins();
                $gamesPlayed = $player->getGamesPlayed();

                //Победители
                if (in_array($team, $roundResult['winners'])) {
                    $player->setWins(++$wins);
                    $player->setGamesPlayed(++$gamesPlayed);
                    $player->save();
                    //Проигравшие
                } elseif (!in_array($team, [Players::STATUS_WAIT, Players::STATUS_OUT]) && !$player->getIsSuspended()) {
                    $player->setGamesPlayed(++$gamesPlayed);
                    $player->setLifes(--$lifes);
                    if ($player->getLifes() <= 0) {
                        $player->setLifes(0);
                        $player->setTeam(Players::STATUS_OUT);
                    }
                    $player->save();
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
                $this->reward();
            }

            $this->save();
        }
    }

    /**
     * Применяет выбранные действия к игрокам с жеребьевкой
     * @param array $roundResult
     */
    public function sendHome(array $roundResult)
{
    $waitingPlayers = (new PlayersTDG())->getPossibleChangePlayers($this);

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

    $this->setPlayers();

    $alivePlayers = $this->getAlivePlayers();

    if (count($alivePlayers) >= 10) {
        $teams = $this->setTeams();
        $this->toss($teams);
    } else {
        $this->setStatus(self::STATUS_ENDED);
        $this->reward();
    }

    $this->save();
}

    /**
     * Применяет выбранные действия к игрокам без жеребьевки
     * @param array $roundResult
     */
    public function sendHomeWithoutToss(array $roundResult)
    {
        $changePlayers = (new PlayersTDG())->getPossibleChangePlayers($this);

        foreach ($roundResult['sendHome'] as $id => $action) {
            /** @var Players $player */
            $player = (new PlayersTDG())->getPlayersbyTournamentID($this->id, $id)[0];
            $team = $player->getTeam();
            if ($changePlayers) {
                $join = isset($join) ? next($changePlayers) : current($changePlayers);
            }
            switch ($action) {
                case '1': //Убрать с посева
                    if ($join) {
                        $join->setTeam($team);
                    }
                    $player->setTeam(Players::STATUS_WAIT);
                    $player->setIsSuspended(true);
                    break;
                case '2': //Убрать жизнь и снять с посева
                    if ($join) {
                        $join->setTeam($team);
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
                        $join->setTeam($team);
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
            } else { //если не нашлось замены - шлем всю команду на банку
                if(!($team == Players::STATUS_WAIT && $team == Players::STATUS_OUT)) {
                    foreach ($this->toss as $key => $pair) {
                        if (in_array($team, $pair)) {
                            foreach($pair as $team) {
                                $players = $this->getPlayersByTeam($team);
                                foreach ($players as $player) {
                                    $player->setTeam(Players::STATUS_WAIT);
                                    $player->save();
                                }
                            }
                            unset($this->toss[$key]);
                            break;
                        }
                    }

                }
            }
        }

        $this->setPlayers();

        $playersInGame = (new PlayersTDG())->getPlayersInGame($this);

        if (!count($playersInGame) >= 10) {
            $this->setStatus(self::STATUS_ENDED);
            $this->reward();
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
            $player->reset();
        }

        $this->save();
    }

    /**
     * Присваивает игрокам команды
     * @return array Массив с командами для жеребьевки
     */
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
        /** @var Players $player */
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
        $teamNames = Utils::getDotaTeamNames();

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

    /**
     * Наполняет объект свойствами
     * @param array $values
     */
    public function hydrate(array $values)
    {
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['date'] ? $this->setDate($values['date']) : $this->setDate('');
        $values['prize_pool'] ? $this->setPrizePool($values['prize_pool']) : $this->setPrizePool(null);
        $values['owner_id'] ? $this->setOwnerId($values['owner_id']) : $this->setOwnerId('');
        $values['type'] ? $this->setType($values['type']) : $this->setType(null);
        if (isset($values['players']) && $values['players']) {
            $this->setPlayers($values['players']);
        };
    }

    public function save($mode = 1): bool
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
                    /** @var Players $player */
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

    /**
     * Валидирует объект, возвращает массив с ошибками или null.
     * @return array|null
     * @throws Exception
     */
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
                $date = new DateTime($this->date);
            } catch (Throwable $exception) {
                $errors[] = 'Введена некорректная дата турнира';
            }
            if ($date < new DateTime()) {
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

        if (!$this->type) {
            $errors[] = 'Отсутствуют тип турнира';
        } else {
            if (!in_array($this->type, TournamentFactory::TOURNAMENT_TYPE)) {
                $errors[] = 'Указан некорректный тип турнира';
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
    public function setPlayers(array $players = array()): void
    {
        if ($players) {
            $this->players = array();
            foreach ($players as $player) {
                $this->players[] = new Players(['nickname' => $player['nickname']]);
            }
        } elseif ($this->id) {
            $this->players = (new PlayersTDG())->getPlayersbyTournamentID($this->id);
        }
    }

    /**
     * Проводит жеребьевку среди комманд
     * @param $teams array Массив с коммандами
     * @return array
     */
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

    protected function getAlivePlayers()
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

    public function reward()
    {
        if ($this->prize_pool) {
            $alive = (new PlayersTDG())->getAlivePlayers($this);
            $lifes_1 = array();
            $lifes_2 = array();

            if ($alive) {
                foreach ($alive as $player) {
                    if ($player->getLifes() == 2) {
                        $lifes_2[] = $player;
                    } elseif ($player->getLifes() == 1) {
                        $lifes_1[] = $player;
                    }
                }

                $prize_2_lifes = [];
                $prize_1_lifes = [];

                if (!count($lifes_1)) {
                    $prize_2_lifes = $this->prize_pool / count($lifes_2);
                } elseif (!count($lifes_2)) {
                    $prize_1_lifes = $this->prize_pool / count($lifes_1);
                } else {
                    $prize_2_lifes = ($this->prize_pool * 0.75) / count($lifes_2);
                    $prize_1_lifes = ($this->prize_pool * 0.25) / count($lifes_1);
                    //Если выигрыш игрока с 1 жизнью больше половины от выигрыша игрока с двумя
                    if ($prize_1_lifes > $prize_2_lifes / 2) {
                        $prize_1_lifes = $prize_2_lifes / 2;
                        $winners_pool = $this->prize_pool - count($lifes_1) * $prize_1_lifes;
                        $prize_2_lifes = $winners_pool / count($lifes_2);
                    }
                }

                //Раздаем призы
                foreach ($lifes_1 as $player) {
                    $player->setPrize($prize_1_lifes);
                    $player->save();
                }
                foreach ($lifes_2 as $player) {
                    $player->setPrize($prize_2_lifes);
                    $player->save();
                }
            }
        }
        $this->setPlayers();
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

}