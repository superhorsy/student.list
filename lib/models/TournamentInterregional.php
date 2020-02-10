<?php


namespace App\models;

use App\components\EndConditions;
use App\components\TournamentInterface;
use App\lib\Arr;
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
        }

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
            $regions = $this->regions;
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
     * @param array $regions
     */
    public function setRegions(array $regions): void
    {
        $this->regions = $regions;
    }

    /**
     * Раскидываем игроков по командам
     * @return array Массив с командами по регионам для жеребьевки
     */
    private function setTeams()
    {
        $waited = $other = $toPlay = $toWait = $toMix = [];

        foreach ($this->getAlivePlayers() as $player) {
            //Отстраненных - к ожидающим
            if ($player->is_suspended) {
                $toWait[] = $player;
            } else if ($player->getTeam() == Players::STATUS_WAIT) {
                //Игроки, ждавшие в прошлом раунде
                $waited[] = $player;
            } else {
                $other[] = $player;
            }
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

        $teams = [];
        $i = 1;

        //Раскручиваем каждый регион
        foreach ($playersByRegion as $regionName => $players) {

            if (count($players) >= 5) {
                $toWait = array_merge($toWait, array_splice($players, -(count($players) % 5), count($players) % 5));
                foreach ($players as $player) {
                    $toPlay[] = $player;
                    $player->setTeam(current($teamNames));
                    $player->save();
                    if ($i == 5) {
                        $i = 1;
                        $teams[$regionName][] = current($teamNames);
                        next($teamNames);
                    } else {
                        $i++;
                    }
                }
            } elseif (count($players) < 5) {
                $toMix = array_merge($toMix, array_splice($players, -(count($players) % 5), count($players) % 5));

                foreach ($players as $player) {
                    $toPlay[] = $player;
                    $player->setTeam(current($teamNames));
                    $player->save();
                    if ($i == 5) {
                        $i = 1;
                        $teams[$regionName][] = current($teamNames);
                        next($teamNames);
                    } else {
                        $i++;
                    }
                }
            }
        }

        $last = array_splice($toMix, -(count($toMix) % 5), count($toMix) % 5);

        //Команды для игроков из неполных команд
        foreach ($toMix as $player) {
            $toPlay[] = $player;
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

        $withoutPair = array_splice($toPlay, -(count($toMix) % 10), count($toMix) % 10);

        //Ждущие + последние игроки из миксуемых (не хватит на команду) + игроки из непарной команды
        foreach (array_merge($last, $toWait, $withoutPair) as $player) {
            $player->setTeam(Players::STATUS_WAIT);
            $player->save();
        }

        //Обновляем игроков в турнире
        $this->setPlayers();

        return $teams;
    }

    /**
     * Проводит жеребьевку
     * @return array
     */
    public function toss()
    {
        //Массив с коммандами, разбитыми по регионам [Регион=>[Команда, Команда ..]]
        $teams = $this->setTeams();

        $regions = array_keys($teams);

        $teamsNames = Arr::flatten($teams);

        $teamsCount = count($teamsNames);

        //Распределенные команды
        $sortedTeams = [];

        while ($teamsCount != count($sortedTeams)) {
            $team = array_shift($teamsNames);
            $region = $this->findRegionByTeam($team,$teams);
            Arr::forget($teams, $region . "." . $team);


        }

        /*foreach ($teams as $region => $regionalTeams) {
            foreach ($regionalTeams as $teamName) {


                if (!in_array($teamName, array_merge($removedTeams, $sortedTeams))) {
                    $excludedTeams = array_merge([$teamName], $sortedTeams, $removedTeams);
                    if ($lastRegion && $team2 = $this->findTeamByRegions($teams, array_diff($regions, [$region, $lastRegion]), $excludedTeams)) {
                        $sortedTeams[] = $teamName;
                        $sortedTeams[] = $team2['team'];
                    } elseif ($team2 = $this->findTeamByRegions($teams, array_diff($regions, [$region]), $excludedTeams)) {
                        $sortedTeams[] = $teamName;
                        $sortedTeams[] = $team2['team'];
                    } elseif ($team2 = $this->findTeamByRegions($teams, ['MIX'], $excludedTeams)) {
                        $sortedTeams[] = $teamName;
                        $sortedTeams[] = $team2['team'];
                    } elseif ($team2 = $this->findTeamByRegions($teams, [$region], $excludedTeams)) {
                        $sortedTeams[] = $teamName;
                        $sortedTeams[] = $team2['team'];
                    } else {
                        $players = $this->getPlayersByTeam($teamName);
                        foreach ($players as $player) {
                            $player->setTeam(Players::STATUS_WAIT);
                            $player->save();
                        }
                        $removedTeams[] = $teamName;
                    }
                    $lastRegion = $team2['region'];
                }
            }
        }*/

        $this->toss = $this->sortTeamsByGroups($sortedTeams);

        $this->setPlayers();

        return $this->toss;
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
    public
    function sendHomeWithoutToss(array $roundResult)
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
            $this->setStatus(self::STATUS_ENDED);
            $this->reward();
        }

        $this->save();
    }

    protected function checkIfTournamentShouldContinue(): bool
    {
        $alivePlayers = $this->getAlivePlayers();

        if (count($alivePlayers) < 10) {
            return false;
        }

        $countPlayersByRegions = $this->getRegionsToPlayerCount($alivePlayers);

        return EndConditions::checkIfShouldContinueTournament($countPlayersByRegions);
    }

    private function getRegionsToPlayerCount($players): array
    {

        $playersRegions = array_column($players, 'region');
        $countPlayersByRegions = array_count_values($playersRegions);
        return $countPlayersByRegions;
    }

    private function findRegionByTeam($team, array $teams)
    {
        foreach ($teams as $region => $regionTeams) {
            foreach ($regionTeams as $regionTeam) {
                if ($regionTeam == $team) return $region;
            }
        }
    }
}