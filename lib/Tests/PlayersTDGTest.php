<?php

namespace App\Tests;

class PlayersTDGTest extends \PHPUnit_Framework_TestCase
{

    public function testGetPlayersbyTournamentID()
    {
        $obj = (new PlayersTDG())->getPlayersbyTournamentID(7);
        $this->assertContainsOnlyInstancesOf('\App\Models\Players', $obj);
    }
}
