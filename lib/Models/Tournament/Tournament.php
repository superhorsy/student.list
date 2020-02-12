<?php


namespace App\Models\Tournament;


use App\Components\Exceptions\CantBeContinuedException;
use App\Components\Utils;
use App\Models\Player\Players;
use App\Models\Player\PlayersTDG;
use App\Models\Tournament\Interfaces\TournamentInterface;
use DateTime;
use Exception;
use Throwable;

/**
 * Class Tournament
 * @package App\Models
 */
class Tournament implements TournamentInterface
{
    protected $tdg;
    protected $playersTdg;

    protected $id = null;
    protected $name = null;
    protected $date = null;
    protected $owner_id = null;

    protected $status;
    /*Статусы турнира*/
    const STATUS_AWAITING = 'awaiting';
    const STATUS_IN_PROGRESS = 'in progress';
    const STATUS_ENDED = 'ended';

    protected $current_round;
    protected $round_count;
    protected $toss;
    protected $prize_pool;

    protected $type;

    protected $players = array();
    protected $loosers = array();

    /**
     *
     * @param $tdg
     */
    public function __construct()
    {
        $this->tdg = new TournamentTDG();
        $this->playersTdg = new PlayersTDG();

        if ($this->id) {
            $this->setPlayers();
        }
        if ($this->toss) {
            $this->toss = json_decode($this->toss, true);
        }
    }

    /**
     * Get a data by key
     *
     * @param string The key data to retrieve
     * @access public
     */
    public function &__get($key)
    {
        return $this->$key;
    }

    /**
     * Whether or not an data exists by key
     *
     * @param string An data key to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function __isset($key)
    {
        return isset($this->$key);
    }

    public function start()
    {
        if ($this->status === self::STATUS_IN_PROGRESS) return;

        $this->setStatus(self::STATUS_IN_PROGRESS);

        try {
            $this->toss();
        } catch (CantBeContinuedException $exception) {
            $this->end();
        }

        $this->current_round = 1;

        $round_count = (count($this->players) - (count($this->players) % 10)) / 5;
        $this->round_count = $round_count;

        $this->save();
    }

    /**
     * Проводит жеребьевку среди комманд
     * @return array
     */
    public function toss(): void
    {
        $teams = $this->setTeams();

        $this->toss = $this->sortTeamsByGroups($teams);
    }

    /**
     * Присваивает игрокам команды
     * @return array Массив с командами для жеребьевки
     * @throws CantBeContinuedException
     */
    private function setTeams()
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

        $teamsNumber = intval(count($players) / 10) * 2;

        if ($teamsNumber < 2) throw new  CantBeContinuedException("Not enough players to continue");

        $teamKeys = array_rand($teamNames, $teamsNumber);

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

    public function getAlivePlayers()
    {
        return (new PlayersTDG())->getAlivePlayers($this);
    }

    public function getWaitingPlayers(): array
    {
        return (new PlayersTDG())->getWaitingPlayers($this);
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
                }
                break;
            case 2: //редактирование турнира с удалением игроков
                (new PlayersTDG())->deleteAllPlayers($this);
                foreach ($this->players as $player) {
                    $player->setTournamentId($this->id);
                    $player->save();
                }
                break;
        }

        return $this->id ? true : false;
    }

    /**
     * Подводим результаты раунда
     *
     * @param array $roundResult
     */
    public function next(array $roundResult)
    {
        if (!$this->status == self::STATUS_IN_PROGRESS) {
            return;
        }

        (new PlayersTDG())->resetSuspension($this);

        $this->updatePlayersAfterRoundResults($roundResult);

        $shouldContinue = $this->checkIfTournamentShouldContinue();

        if ($shouldContinue) {
            $this->toss();
            $this->current_round++;
            $this->save();
        } else {
            $this->end();
        }
    }

    private function updatePlayersAfterRoundResults(array $roundResult)
    {
        /** @var Players $player */
        foreach ($this->players as $player) {
            $team = $player->getTeam();
            $lives = $player->getLives();
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
                $player->setLives(--$lives);
                if ($player->getLives() <= 0) {
                    $player->setLives(0);
                    $player->setTeam(Players::STATUS_OUT);
                }
                $player->save();
            }
        }

        $this->setPlayers();
    }

    protected function checkIfTournamentShouldContinue(): bool
    {
        $alivePlayers = $this->getAlivePlayers();
        return count($alivePlayers) >= 10;
    }

    public function reward()
    {
        if (!$this->prize_pool) return;

        $alive = (new PlayersTDG())->getAlivePlayers($this);

        if (!$alive) return;

        $lives = array_column($alive, 'lives');
        $countLives = array_count_values($lives);

        $N2 = $countLives[2] ?? 0;
        $N1 = $countLives[1] ?? 0;

        if ($N2 !== 0 && $N1 !== 0) {
            $twoLifePrize = ($this->prize_pool / ($N2 * 3 + $N1)) * ($N2 * 3) / $N2;
            $oneLifePrize = $twoLifePrize / 3;
        } else {
            if ($N2 == 0) {
                $twoLifePrize = 0;
                $oneLifePrize = $this->prize_pool / $N1;
            }
            if ($N1 == 0) {
                $oneLifePrize = 0;
                $twoLifePrize = $this->prize_pool / $N2;
            }
        }


        //Раздаем призы
        foreach ($alive as $player) {
            switch ($player->lives) {
                case 2:
                    $player->setPrize((int)$twoLifePrize);
                    $player->save();
                    break;
                case 1:
                    $player->setPrize((int)$oneLifePrize);
                    $player->save();
                    break;
            }
        }

        $this->setPlayers();
    }

    /**
     * Применяет выбранные действия к игрокам с жеребьевкой
     *
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
                    $player->setLives($player->getLives() - 1);
                    if ($player->getLives() < 1) {
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
                    $player->setLives(0);
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

        if ($this->checkIfTournamentShouldContinue()) {
            $this->toss();
            $this->save();
        } else {
            $this->end();
        }
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
                    $player->setLives($player->getLives() - 1);
                    if ($player->getLives() < 1) {
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
                    $player->setLives(0);
                    $player->setTeam(Players::STATUS_OUT);
                    $player->setIsSuspended(true);
                    break;
                default:
                    continue 2;
            }
            $player->save();

            if ($join) {
                $join->save();
            } else {
                //если не нашлось замены - шлем всю команду на банку
                if (!($team == Players::STATUS_WAIT && $team == Players::STATUS_OUT)) {
                    foreach ($this->toss as $key => $pair) {
                        if (in_array($team, $pair)) {
                            foreach ($pair as $team) {
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
            $this->end();
        } else {
            $this->save();
        }
    }

    public function getPlayersByTeam($team)
    {
        return (new PlayersTDG())->getPlayersbyTeam($this, $team);
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

    public function getEstimation(): int
    {
        foreach ($this->players as $player) {
            $players[] = $player->getLives(); //each player has 2 lives at the start
        }

        $cycles = 15;

        for ($i = 1; $i <= $cycles; $i++) {
            $estimation[] = Utils::estimateRounds($players);
        }

        return round(array_sum($estimation) / $cycles, 0, PHP_ROUND_HALF_UP);
    }

    /**
     * Наполняет объект свойствами
     * @param array $values
     */
    public function hydrate(array $values)
    {
        $this->setName($values['name'] ?? '');
        $this->setDate($values['date'] ?? '');
        $this->setPrizePool($values['prize_pool'] ?? null);
        $this->setOwnerId($values['owner_id'] ?? '');
        $this->setType($values['type'] ?? null);

        if (isset($values['players']) && $values['players']) {
            $this->setPlayers($values['players']);
        }
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
                }
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
     * @param null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
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
     * @param mixed $current_round
     */
    public function setCurrentRound($current_round): void
    {
        $this->current_round = $current_round;
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
    public function getToss()
    {
        return $this->toss;
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
    public function getLoosers(): array
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

    public function getPlayersOrderedByLives()
    {
        return (new PlayersTDG())->getPlayersOrderedByLives($this);
    }

    /**
     * @param mixed $prize_pool
     */
    public function setPrizePool($prize_pool): void
    {
        $this->prize_pool = $prize_pool;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @param array $teams
     * @return array
     */
    protected function sortTeamsByGroups(array $teams): array
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
        return $toss;
    }

    public function end(): void
    {
        $this->setStatus(self::STATUS_ENDED);
        $this->reward();
        $this->save();
    }

    public function swapPlayersTeams(array $playerIds)
    {
        $player1 = $this->playersTdg->getPlayerbyID($playerIds[0]);
        $player2 = $this->playersTdg->getPlayerbyID($playerIds[1]);

        $team = $player1->getTeam();

        $player1->setTeam($player2->getTeam());
        $player2->setTeam($team);

        $player1->save();
        $player2->save();
    }
}