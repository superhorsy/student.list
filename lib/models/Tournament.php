<?php


namespace App\models;


class Tournament
{
    private $id = null;
    private $name = null;
    private $datetime = null;
    private $owner_id = null;
    private $players = array();

    public function hydrate(array $values) {
        $values['id'] ? $this->setId($values['id']) : $this->setId('');
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['datetime'] ? $this->setDatetime($values['datetime']) : $this->setDatetime('');
        $values['owner_id'] ? $this->setOwnerId($values['owner_id']) : $this->setOwnerId('');
        //Отдельно формируем игроков
        if($values['players'] && is_array($values['players'])) {
            $this->setPlayers($values['players']);
        }
    }

    public function isValid():?array {
        $errors = [];
        if (!$this->name) {
            $errors[] = 'Не введено наименование турнира';
        }
        if (!$this->datetime) {
            $errors[] = 'Отсутствует дата турнира';
        } else {
            try {
                $datetime = new \DateTime($this->datetime);
            } catch(\Throwable $exception) {
             $errors[] = 'Введена некорректная дата турнира';
            }
            if ($datetime < new \DateTime()) {
                $errors[] = 'Дата турнира не может быть раньше текущей даты';
            }
        }

        if (!$this->datetime()) {
            $errors[] = 'Отсутствует дата турнира';
        }
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param array $players
     */
    public function setPlayers(array $players): void
    {
        $playersTDG = new PlayersTDG();
        $this->players = $players;
    }

    /**
     * @return null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param null $id
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param null $name
     */
    public function setName($name): void
    {
        $this->name = $name;
    }

    /**
     * @return null
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @param null $datetime
     */
    public function setDatetime($datetime): void
    {
        $this->datetime = $datetime;
    }

    /**
     * @return null
     */
    public function getOwnerId()
    {
        return $this->owner_id;
    }

    /**
     * @param null $owner_id
     */
    public function setOwnerId($owner_id): void
    {
        $this->owner_id = $owner_id;
    }

}