<?php


namespace App\models;

use App\components\TournamentInterface;
use App\Utils;

class TournamentInterregional extends Tournament implements TournamentInterface
{
    private $regions = [];

    public function __construct()
    {
        parent::__construct();
        if ($this->regions) {
            $this->regions = json_decode($this->regions);
        }
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
        if ($values['regions'] && !empty($values['regions'])) {
            array_map('trim', $values['regions']);
            $this->setRegions($values['regions']);
        }
        if (isset($values['players']) && $values['players']) {
            $this->setPlayers($values['players'], $values['regions']);
        };

    }

    /**
     * @param array $data
     */
    public function setPlayers(array $players = array()): void
    {
        if ($players) {
            $this->players = array();
            foreach ($players as $player) {
                $this->players[] = new Players(['nickname' => $player['nickname'], 'region' => $player['region']]);
            }
        } elseif ($this->id) {
            $this->players = (new PlayersTDG())->getPlayersbyTournamentID($this->id);
        }
    }

    public function isValid(): ?array
    {
        $validation = parent::isValid();
        $errors = [];
        if ($this->players) {
            $regions = $this->getRegions();
            foreach ($this->players as $player) {
                if (!in_array($player->getRegion(), $regions)) {
                    $errors[] = "Для игрока {$player->getNickname()} указан некорректный регион";
                }
            }
        }

        $errors = $validation ? array_merge($validation, $errors) : $errors;

        return empty($errors) ? null : $errors;
    }

    /**
     * @return array|null
     */
    public function getRegions(): ?array
    {
        return $this->regions;
    }

    /**
     * @param array $regions
     */
    public function setRegions(array $regions): void
    {
        $this->regions = $regions;
    }

    /**
     * Присваивает игрокам команды
     * @return array Массив с командами для жеребьевки
     */
    public function setTeams()
    {
        $waited = $suspended = $other = [];
        foreach ($this->getAlivePlayers() as $player) {
            if ($player->getIsSuspended()) {
                $suspended[] = $player;
            } else if ($player->getTeam() == Players::STATUS_WAIT) {
                $waited[] = $player;
            } else {
                $other[] = $player;
            }
        }

        foreach ($suspended as $player) {
            $player->setTeam(Players::STATUS_WAIT);
            $player->save();
        }

        //Имена комманд
        $teamNames = Utils::getDotaTeamNames();
        shuffle($teamNames);
        shuffle($waited);
        shuffle($other);

        //Получаем игроков для каждого региона
        $playersByRegion = [];
        //Сначала ждавшие
        foreach ($waited as $player) {
            $playersByRegion[$player->getRegion()][] = $player;
        }
        //Затем остальные
        foreach ($other as $player) {
            $playersByRegion[$player->getRegion()][] = $player;
        }

        //Игроки, оставшиеся вне команд по 5 человек
        $leftovers = [];

        $teams = [];
        $i = 1;

        //Раскручиваем каждый регион
        foreach ($playersByRegion as $name => $region) {
            $leftovers = array_merge($leftovers, array_splice($region, -(count($region) % 5), count($region) % 5));
            foreach ($region as $player) {
                $player->setTeam(current($teamNames));
                $player->save();
                if ($i == 5) {
                    $i = 1;
                    $teams[$name][] = current($teamNames);
                    next($teamNames);
                } else {
                    $i++;
                }
            }
        }

        $last = array_splice($leftovers, -(count($leftovers) % 5), count($leftovers) % 5);

        //Команды для игроков из неполных команд
        foreach ($leftovers as $player) {
            $player->setTeam(current($teamNames));
            $player->save();
            if ($i == 5) {
                $i = 1;
                $teams['MIX'][] = current($teamNames);
                next($teamNames);
            } else {
                $i++;
            }
        }
        //Последние игроки (не хватит на команду)
        if ($last) {
            foreach ($last as $player) {
                $player->setTeam(Players::STATUS_WAIT);
                $player->save();
            }
        }
        //Обновляем игроков в турнире
        $this->setPlayers();

        return $teams;
    }

    /**
     * Проводит жеребьевку среди комманд
     * @param $teams array Массив с коммандами, разбитыми по регионам [Регион=>[Команда, Команда ..]]
     * @return array
     */
    public function toss($teams)
    {
        $regions = array_keys($teams);
        //Команды попадающие в игру
        $sortedTeams = [];
        //Расформированные команды
        $removedTeams = [];
        //Регион последней из выбранных команд
        $lastRegion = null;

        foreach ($teams as $region => $regionalTeams) {
            foreach ($regionalTeams as $key => $team1) {
                if (!in_array($team1, array_merge($removedTeams, $sortedTeams))) {
                    $excludedTeams = array_merge([$team1], $sortedTeams, $removedTeams);
                    if ($lastRegion && $team2 = $this->findTeamByRegions($teams, array_diff($regions, [$region, $lastRegion]), $excludedTeams)) {
                        $sortedTeams[] = $team1;
                        $sortedTeams[] = $team2['team'];
                    } elseif ($team2 = $this->findTeamByRegions($teams, array_diff($regions, [$region]), $excludedTeams)) {
                        $sortedTeams[] = $team1;
                        $sortedTeams[] = $team2['team'];
                    } elseif ($team2 = $this->findTeamByRegions($teams, ['MIX'], $excludedTeams)) {
                        $sortedTeams[] = $team1;
                        $sortedTeams[] = $team2['team'];
                    } elseif ($team2 = $this->findTeamByRegions($teams, [$region], $excludedTeams)) {
                        $sortedTeams[] = $team1;
                        $sortedTeams[] = $team2['team'];
                    } else {
                        $players = $this->getPlayersByTeam($team1);
                        foreach ($players as $player) {
                            $player->setTeam(Players::STATUS_WAIT);
                            $player->save();
                        }
                        $removedTeams[] = $team1;
                    }
                    $lastRegion = $team2['region'];
                }
            }
        }

        //Распределяем команды по группам
        $groupNumber = 'A';
        $toss[$groupNumber] = array();
        $group = array();
        foreach ($sortedTeams as $teamName) {
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

        $this->setPlayers();

        return $toss;
    }

    /**
     * Забирает допустимое значение из массива с командами
     * @param array $teams
     * @param array $acceptableRegions
     * @return array|bool|mixed
     */
    private function findTeamByRegions(array $teams, array $acceptableRegions, $excludedTeams = [])
    {
        foreach ($teams as $region => $teams) {
            if (!empty($teams) && in_array($region, $acceptableRegions)) {
                foreach ($teams as $index => $team) {
                    if (!in_array($team, $excludedTeams)) {
                        return ['team' => $team,
                            'region' => $region];
                    }
                }
            }
        }
        return false;
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
            $region = $player->getRegion();

            $join = false;
            if ($changePlayers && !in_array($region, [Players::STATUS_OUT, Players::STATUS_WAIT])) {
                /** @var Players $changePlayer */
                foreach ($changePlayers as $key => $changePlayer) {
                    $playerRegion = $changePlayer->getRegion();
                    if ($playerRegion == $region) {
                        $join = $changePlayer;
                        unset($changePlayers[$key]);
                        break;
                    }
                }
            }
            if (!$join && $changePlayers) {
                shuffle($changePlayers);
                $join = array_shift($changePlayers);
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
            $this->setStatus(self::STATUS_ENDED);
            $this->reward();
        }

        $this->save();
    }
}