<?php


namespace App\models;


class Toss
{

    /**
     * Toss constructor.
     */
    public function __construct(Tournament $tournament)
    {
        $players = $tournament->getPlayers();
        if (empty($players)) {
            return false;
        }

        $players

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

        /*$this->tdg->updateValues(['toss' => $this->current_toss], $this->id);*/

        return $this->current_toss;
    }
}