<?php


namespace App\models;


class Players
{
    private $tdg;

    private $id;
    private $nickname;
    private $group;
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

    public function save()
    {
        return $this->tdg->insertValues([
            'nickname'=>$this->nickname,
            'tournament_id'=>$this->tournament_id,
        ]) ? true : false;
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
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * @param mixed $group
     */
    public function setGroup($group): void
    {
        $this->group = $group;
        $this->tdg->setGroup($this);
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