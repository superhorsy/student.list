<?php


namespace App\Models\Tournament;

use App\Components\EndConditions;
use App\Models\Player\Players;
use App\Models\Player\PlayersTDG;
use App\Models\Tournament\nterfaces\TournamentInterface;

class TournamentInterregional extends Tournament implements TournamentInterface
{
    protected $regions = [];

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
        parent::hydrate($values);

        if ($values['regions'] && !empty($values['regions'])) {
            array_map('trim', $values['regions']);
            $this->setRegions($values['regions']);
        }
    }

    /**
     * @param array $regions
     */
    public function setRegions(array $regions): void
    {
        $this->regions = $regions;
    }

    /**
     * @param array $data
     */
    public function setPlayers(array $players = array()): void
    {
        if ($players) {
            $this->players = array();
            foreach ($players as $player) {
                $this->players[] = new Players([
                    'nickname' => $player['nickname'],
                    'region' => $player['region']
                ]);
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
                if (!in_array($player->region, $regions)) {
                    $errors[] = "Для игрока {$player->getNickname()} указан некорректный регион";
                }
            }
        }

        $errors = $validation ? array_merge($validation, $errors) : $errors;

        return empty($errors) ? null : $errors;
    }

    /**
     * Проводит жеребьевку
     * @return array
     */
    public function toss(): void
    {
        $toss = new TossInterregional($this);

        $this->toss = $toss->run();

        $this->players = $toss->getPlayers();

        /** @var Players $player */
        foreach ($this->players as $player) {
            $player->save();
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
            $region = $player->region;

            $join = false;
            if ($changePlayers && !in_array($region, [Players::STATUS_OUT, Players::STATUS_WAIT])) {
                /** @var Players $changePlayer */
                foreach ($changePlayers as $key => $changePlayer) {
                    $playerRegion = $changePlayer->region;
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
}