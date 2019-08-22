<?php


namespace App\models;

class Players implements \Serializable
{
    private $tdg;

    private $id;
    private $nickname;
    private $team;
    private $tournament_id;
    private $lifes;

    /**
     * Players constructor.
     * @param $tdg
     */
    public function __construct()
    {
        $tdg = new PlayersTDG();
        $this->tdg = $tdg;
    }

    /**
     * String representation of object
     * @link https://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize()
    {
       return serialize(
            $this->id,
            $this->nickname,
            $this->tournament_id,
            $this->lifes
        );
    }

    /**
     * Constructs the object
     * @link https://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized)
    {
        $tdg = new PlayersTDG();
        $this->tdg = $tdg;
        list(
            $this->id,
            $this->nickname,
            $this->tournament_id,
            $this->lifes
            ) = unserialize($serialized);

    }

    public function save()
    {
        $insertValues = [
            'nickname'=>$this->nickname,
            'tournament_id'=>$this->tournament_id,
        ];

        if (isset($this->lifes)) {
            $lifes = ['lifes' => $this->lifes];
            $insertValues = array_merge($insertValues,$lifes);
        }
        if (isset($this->team)) {
            $team = ['team' => $this->team];
            $insertValues = array_merge($insertValues,$team);
        }

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
    public function getLifes()
    {
        return $this->lifes;
    }

    /**
     * @param null $lifes
     */
    public function setLifes($lifes): void
    {
        $this->lifes = $lifes;
    }
}