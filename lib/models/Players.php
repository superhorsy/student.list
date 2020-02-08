<?php


namespace App\models;

class Players
{
    private $tdg;

    private $id;
    private $nickname;
    private $team;
    private $tournament_id;
    private $lives;
    private $is_suspended;
    private $prize = null;
    private $region = null;
    private $wins = null;
    private $games_played = null;

    //Player status
    const STATUS_WAIT = 'WAIT';
    const STATUS_OUT = 'OUT';

    /**
     * Players constructor.
     * @param $tdg
     */
    public function __construct(array $data = array())
    {
        $tdg = new PlayersTDG();
        if ($data) {
            $this->hydrate($data);
        }
        $this->tdg = $tdg;
    }

    public function __toString()
    {
        return "Ник - {$this->nickname}, жизней - {$this->lives}, игр - {$this->games_played}, побед - {$this->wins}, 
        отстранен - {$this->is_suspended}";
    }

    /**
     * Get a data by key
     *
     * @param string The key data to retrieve
     * @access public
     */
    public function &__get ($key) {
        return $this->$key;
    }

    /**
     * Whether or not an data exists by key
     *
     * @param string An data key to check for
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function __isset ($key) {
        return isset($this->$key);
    }

    /**
     * Сохраняет текущие параметры игрока в базу
     * @return bool
     */
    public function save()
    {
        $insertValues = [
            'nickname' => $this->nickname,
            'region' => $this->region,
            'tournament_id' => $this->tournament_id,
        ];

        if (isset($this->lives)) {
            $insertValues = array_merge($insertValues, ['lives' => $this->lives]);
        }

        if (isset($this->wins)) {
            $insertValues = array_merge($insertValues, ['wins' => $this->wins]);
        }

        if (isset($this->games_played)) {
            $insertValues = array_merge($insertValues, ['games_played' => $this->games_played]);
        }

        $insertValues = array_merge($insertValues, ['team' => $this->team]);

        if (isset($this->is_suspended)) {
            $insertValues = array_merge($insertValues, ['is_suspended' => $this->is_suspended]);
        }

        $insertValues = array_merge($insertValues, ['prize' => $this->prize ? $this->prize : null]);


        if ($this->getId()) {
            return $this->tdg->updateValues($insertValues, $this->getId()) ? true : false;
        } else {
            return $this->tdg->insertValues($insertValues) ? true : false;
        }
    }


    public function hydrate(array $values)
    {
        $values['id'] ? $this->setId($values['id']) : $this->setId('');
        $values['nickname'] ? $this->setNickname($values['nickname']) : $this->setNickname('');
        $values['tournament_id'] ? $this->setTournamentId($values['tournament_id']) : $this->setTournamentId('');
        $values['prize'] ? $this->setPrize($values['prize']) : $this->setPrize('');
        $values['region'] ? $this->setRegion($values['region']) : $this->setRegion(null);
    }

    public function reset()
    {
        $this->setLives(2);
        $this->setGamesPlayed(0);
        $this->setWins(0);
        $this->setTeam(null);
        $this->setIsSuspended(0);
        $this->setPrize(null);
        $this->save();
    }

    public function isValid(): ?array
    {
        $errors = array();
        static $emptyNicknameError = null;
        if (!($this->nickname) && !$emptyNicknameError) {
            $errors[] = 'Заполнены не все ники игроков';
            $emptyNicknameError = true;
        } elseif (mb_strlen($this->nickname) > 50) {
            $errors[] = 'Никнэйм игрока может быть не больше 50 символов';
        }

        return $errors ? $errors : null;
    }

    /**
     * @return mixed
     */
    public function getTournamentId()
    {
        return $this->tournament_id;
    }

    /**
     * @param mixed $tournament_id
     */
    public function setTournamentId($tournament_id): void
    {
        $this->tournament_id = $tournament_id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getNickname()
    {
        return $this->nickname;
    }

    /**
     * @param mixed $nickname
     */
    public function setNickname($nickname): void
    {
        $this->nickname = $nickname;
    }

    /**
     * @return mixed
     */
    public function getTeam()
    {
        return $this->team;
    }

    /**
     * @param mixed $team
     */
    public function setTeam($team): void
    {
        $this->team = $team;
    }

    /**
     * @return null
     */
    public function getLives()
    {
        return $this->lives;
    }

    /**
     * @param null $lives
     */
    public function setLives($lives): void
    {
        $this->lives = $lives;
    }

    /**
     * @return mixed
     */
    public function getIsSuspended()
    {
        return $this->is_suspended;
    }

    /**
     * @param mixed $is_suspended
     */
    public function setIsSuspended($is_suspended): void
    {
        $this->is_suspended = $is_suspended;
    }

    /**
     * @return mixed
     */
    public function getPrize()
    {
        return $this->prize;
    }

    /**
     * @param mixed $prize
     */
    public function setPrize($prize): void
    {
        $this->prize = $prize;
    }

    /**
     * @return null
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param null $region
     */
    public function setRegion($region): void
    {
        $this->region = $region;
    }

    /**
     * @return null
     */
    public function getWins()
    {
        return $this->wins;
    }

    /**
     * @param null $wins
     */
    public function setWins($wins): void
    {
        $this->wins = $wins;
    }

    /**
     * @return null
     */
    public function getGamesPlayed()
    {
        return $this->games_played;
    }

    /**
     * @param null $games_played
     */
    public function setGamesPlayed($games_played): void
    {
        $this->games_played = $games_played;
    }
}