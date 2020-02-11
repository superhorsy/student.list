<?php

namespace App\Tests;

class TournamentTDGTest extends \PHPUnit_Framework_TestCase
{

    public function testGetTournamentsByUser()
    {
        $tdg = new TournamentTDG();
        $tournaments = $tdg->getTournamentsByUser(14);
        $this->assertContainsOnlyInstancesOf('\App\\Models\tournament\Tournament', $tournaments);
        foreach ($tournaments as $tournament) {
            $players = $tournament->getPlayers();
            $this->assertContainsOnlyInstancesOf('\App\Models\Players', $players);
        }
    }

    public function testGetTournamentById()
    {
        $tdg = new TournamentTDG();
        $tournaments = $tdg->getTournamentById(8);
        $this->assertInstanceOf('\App\\Models\tournament\Tournament', $tournaments);

    }

    public function testSetTeams()
    {
        $tdg = new TournamentTDG();
        $tournaments = $tdg->getTournamentById(8);
        $result = $tournaments->setTeams();
        $this->assertNotEmpty($result);
        return $result;
    }

    /**
     * @depends testSetTeams
     */
    public function testToss(array $teams)
    {
        $tdg = new TournamentTDG();
        $tournaments = $tdg->getTournamentById(8);
        $teams = $tournaments->setTeams();
        $result = $tournaments->toss($teams);
        $this->assertNotEmpty($result);
    }
}
