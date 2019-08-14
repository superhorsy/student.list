<?php

namespace App\tests;

use App\models\PlayersTDG;
require_once '../../bootstrap.php';

class PlayersTDGTest extends \PHPUnit_Framework_TestCase
{

    public function testGetPlayersbyTournamentID()
    {
        $obj = (new PlayersTDG())->getPlayersbyTournamentID(7);
        $this->assertContainsOnlyInstancesOf('\App\models\Players', $obj);
    }
}
