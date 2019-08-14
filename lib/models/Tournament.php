<?php


namespace App\models;


class Tournament
{
    private $tdg;

    private $id = null;
    private $name = null;
    private $datetime = null;
    private $owner_id = null;
    private $players = array();
    private $status;

    const STATUS_IN_PROGRESS = 'in progress';

    /**
     * Tournament constructor.
     * @param $tdg
     */
    public function __construct()
    {
        $tdg = new TournamentTDG();
        $this->tdg = $tdg;
        if ($this->id) {
            $this->setPlayers();
        }
    }

    public function start()
    {
        $this->setStatus(self::STATUS_IN_PROGRESS);
        $this->mix();
    }

    public function mix()
    {
        for ($i = 0; $this->players[$i]; $i++) {
            $player = $this->players[$i];
            $groupName = 'A';
            $player->setGroup($groupName);
            if ($i & 5 == 0) {
                $groupName++;
            }
        }
    }

    public function hydrate(array $values)
    {
        $values['id'] ? $this->setId($values['id']) : $this->setId('');
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['datetime'] ? $this->setDatetime($values['datetime']) : $this->setDatetime('');
        $values['owner_id'] ? $this->setOwnerId($values['owner_id']) : $this->setOwnerId('');
        //Отдельно формируем игроков
        if ($values['players'] && is_array($values['players'])) {
            $this->setPlayers($values['players']);
        }
    }

    public function save(): bool
    {
        $tournamentId = $this->tdg->saveTournament($this);

        if (!$tournamentId) {
            return false;
        }

        $this->id = $tournamentId;

        foreach ($this->players as $player) {
            $player->setTournamentId($tournamentId);
            $player->save();
        }

        return $this->id ? true : false;

    }

    public function isValid(): ?array
    {
        $errors = [];

        if (!$this->name) {
            $errors[] = 'Не введено наименование турнира';
        } elseif (strlen($this->name) > 255) {
            $errors[] = 'Наименование турнира не должно превышать 255 символов';
        }

        if (!$this->datetime) {
            $errors[] = 'Отсутствует дата турнира';
        } else {
            try {
                $datetime = new \DateTime($this->datetime);
            } catch (\Throwable $exception) {
                $errors[] = 'Введена некорректная дата турнира';
            }
            if ($datetime < new \DateTime()) {
                $errors[] = 'Дата турнира не может быть раньше текущей даты';
            }
        }

        $playerNicknames = array();
        foreach ($this->players as $player) {
            $playerNicknames[] = $player->getNickname();
            $playerErrors = $player->isValid();
            if ($playerErrors) {
                $errors = array_merge($errors, $playerErrors);
            };
        }
        if (count($playerNicknames) !== count(array_unique($playerNicknames))) {
            $errors[] = 'Никнэймы игроков не могут дублироваться';
        }


        return $errors ? $errors : null;
    }

    /**
     * @return array
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    /**
     * @param array $data
     */
    public function setPlayers(array $nicknames = array()): void
    {
        if ($nicknames) {
            $players = [];
            foreach ($nicknames as $nickname) {
                $player = new Players();
                $playerData = ['nickname' => $nickname];
                $player->hydrate($playerData);
                $this->players[] = $player;
            }
        }
        if ($this->id) {
            $this->players = (new PlayersTDG())->getPlayersbyTournamentID($this->id);
        }
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

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param mixed $status
     */
    public function setStatus($status): void
    {
        $this->status = $status;
    }

}