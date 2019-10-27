<?php


namespace App\models;


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
        if (isset($values['players']) && $values['players']) {
            $this->setPlayers($values['players'], $values['t_regions']);
        };
        if ($values['t_regions'] && !empty($values['t_regions'])) {
            array_map('trim', $values['t_regions']);
            $this->setRegions($values['t_regions']);
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
            $regions = $this->getRegions();
            foreach ($this->players as $player) {
                if (!in_array($regions, $player->getRegion())) {
                    $errors[] = "Для игрока {$player->getName()} указан некорректный регион";
                }
            }
        }

        $errors = $validation ? array_merge($validation, $errors) : $errors;

        return empty($errors) ? null : $errors;
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
     * @return array
     */
    public function getRegions(): array
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
}