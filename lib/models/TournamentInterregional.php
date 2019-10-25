<?php


namespace App\models;


class TournamentInterregional extends Tournament  implements TournamentInterface
{
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
}