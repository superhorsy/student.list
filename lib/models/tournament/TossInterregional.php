<?php


namespace App\models\tournament\interfaces;


use App\components\TournamentInterface;
use App\lib\Arr;
use App\models\Players;
use App\Utils;

class TossInterregional implements Toss
{
    private $tournament;
    private $players;
    private $regions = [];

    public function __construct(TournamentInterface $tournament)
    {
        $this->tournament = $tournament;
        $this->players = $tournament->getAlivePlayers();
    }

    /**
     * Returns toss for current round
     * @return array
     */
    public function run(): array
    {
        $this->setTeams();
        $sortedTeams = $this->sortTeams();
        return $this->sortTeamsByGroups($sortedTeams);
    }

    /**
     * Returns players after tossing
     * @return mixed
     */
    public function getPlayers()
    {
        return $this->players;
    }

    /**
     * Присваивает игрокам команды
     */
    private function setTeams(): void
    {
        $waited = $other = $toPlay = $toWait = $toMix = [];

        /** @var Players $player */
        foreach ($this->players as $player) {
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

        $i = 1;

        //Раскручиваем каждый регион
        foreach ($playersByRegion as $regionName => $players) {

            if (count($players) >= 5) {
                $toWait = array_merge($toWait, array_splice($players, -(count($players) % 5), count($players) % 5));
                foreach ($players as $player) {
                    $toPlay[] = $player;
                    $player->setTeam(current($teamNames));
                    if ($i == 5) {
                        $i = 1;
                        $this->regions[$regionName][] = current($teamNames);
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
                    if ($i == 5) {
                        $i = 1;
                        $this->regions[$regionName][] = current($teamNames);
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
            if ($i == 5) {
                $i = 1;
                $this->regions['MIX'][] = current($teamNames);
                next($teamNames);
            } else {
                $i++;
            }
        }

        $withoutPair = array_splice($toPlay, -(count($toPlay) % 10), count($toPlay) % 10);
        if (!empty($withoutPair)) {
            foreach ($withoutPair as $player) {
                $region = $withoutPair[0]->region;
                $team = $withoutPair[0]->team;
                if (Arr::has($this->regions,$region)) {
                    $teamKey = array_search($team, Arr::get($this->regions, $region));
                    Arr::pull($this->regions, "$region.$teamKey");
                } else {
                    $teamKey = array_search($team, Arr::get($this->regions, 'MIX'));
                    Arr::pull($this->regions, "MIX.$teamKey");
                }
            }
        }

        if (count(Arr::flatten($this->regions)) % 2 !== 0) xdebug_break();
        //Ждущие + последние игроки из миксуемых (не хватит на команду) + игроки из непарной команды
        foreach (array_merge($last, $toWait, $withoutPair) as $player) {
            $player->setTeam(Players::STATUS_WAIT);
        }
    }

    private function sortTeams(): array
    {
        $regions = $this->regions;
        $sortedTeams = [];
        while ($count = Arr::flatten($regions)) {
            $sortedTeams[] = $this->pullTeamFromBiggestRegion($regions);
        }
        return $sortedTeams;
    }

    private function pullTeamFromBiggestRegion(array &$regions)
    {
        $countRegions = [];
        foreach ($regions as $name => $teams) {
            $countRegions[$name] = count($teams);
        };

        $maxRegion = array_search(max($countRegions), $countRegions);
        $teams = Arr::get($regions,$maxRegion);
        $teamKey = array_key_first($teams);

        return Arr::pull($regions, "$maxRegion.$teamKey");
    }

    /**
     * @param array $teams
     * @return array
     */
    private function sortTeamsByGroups(array $teams): array
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
}