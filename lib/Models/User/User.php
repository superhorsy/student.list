<?php


namespace App\Models\User;


class User
{
    private $tdg = null;

    private $id = null;
    private $hash = null;
    private $username = null;
    private $name = null;
    private $email = null;

    public function __construct($idOrUsername = null)
    {
        $db = new UserTDG();
        $this->tdg = $db;
        if (isset($idOrUsername)){
            $userData = $this->tdg->getUserData($idOrUsername);
            $this->hydrate($userData);
        }
    }

    public function hydrate($values){
        $values['id'] ? $this->setId($values['id']) : $this->setId('');
        $values['hash'] ? $this->setHash($values['hash']) : $this->setHash('');
        $values['username'] ? $this->setUsername($values['username']) : $this->setUsername('');
        $values['name'] ? $this->setName($values['name']) : $this->setName('');
        $values['email'] ? $this->setEmail($values['email']) : $this->setEmail('');
    }

    public function save($values) {
        $password = $values['password'];
        unset($values['password']);
        $this->hash = $values['hash'] = password_hash($password, PASSWORD_DEFAULT);
        $this->tdg->insertValues($values);
    }

    public function validate(): array
    {

        array_filter($_POST, function (&$value) {
            trim($value);
            return $value;
        });

        $errors = array();

        $username = filter_input(INPUT_POST, 'username');
        if (!(filter_has_var(INPUT_POST, 'username') &&
            (strlen($username) > 0))) {
            $errors['username'] = 'Отсутствует поле "Имя пользователя"';
        } elseif (!filter_input(INPUT_POST, 'username', FILTER_VALIDATE_REGEXP, [
            'options' => [
                'regexp' => '~^[a-zA-Z\d]{3,20}$~']])) {
            $errors['username'] = 'Имя пользователя должно быть от 3 до 20 символов в длину и может состоять из цифр и латитнских букв';
        } else {
            if (!$this->tdg->isUsernameAvailable($username)) {
                $errors['username'] = 'Данное имя пользователя занято.';
            }
        };

        if (!(filter_has_var(INPUT_POST, 'name') &&
            (strlen(filter_input(INPUT_POST, 'name')) > 0))) {
            $errors['name'] = 'Отсутствует поле "Имя"';
        } else if (strlen(filter_input(INPUT_POST, 'name')) > 50) {
            $errors['name'] = 'Поле "Имя" не может содержать более 50 символов';
        }

        if (!(filter_has_var(INPUT_POST, 'email') &&
            (strlen(filter_input(INPUT_POST, 'email')) > 0))) {
            $errors['email'] = 'Отсутствует поле "Электронная почта"';
        } else {
            $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
            if ($email === false) {
                $errors['email'] = 'Введенный e-mail недействителен';
            } else {
                if (!$this->tdg->isEmailAvailable($email)) {
                    $errors['email'] = 'Данный e-mail уже зарегистрирован в системе.';
                }
            }
        }

        $password = filter_input(INPUT_POST, 'password');
        $passwordAgain = filter_input(INPUT_POST, 'passwordAgain');
        if (!($password && (strlen($password) > 0))) {
            $errors['password'] = 'Отсутствует поле "Пароль"';
        } elseif (!($passwordAgain && (strlen($passwordAgain) > 0))) {
            $errors['passwordAgain'] = 'Отсутствует поле "Подверждение пароля"';
        } else {
            if ($password !== $passwordAgain) {
                $errors['password'] = 'Введенные пароли не совпадают';
            }

            if (!filter_input(INPUT_POST, 'password', FILTER_VALIDATE_REGEXP, [
                'options' => [
                    'regexp' => '~(?=.*[0-9])(?=.*[\p{Lu}]).{8,}~']])) {
                $errors['password'] = 'Пароль должен быть больше 8 символов в длину и содержать хотя бы одну цифру и заглавную букву';
            }
        }

        return $errors;
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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param null $username
     */
    public function setUsername($username): void
    {
        $this->username = $username;
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param null $email
     */
    public function setEmail($email): void
    {
        $this->email = $email;
    }

    /**
     * @return null
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param null $hash
     */
    public function setHash($hash): void
    {
        $this->hash = $hash;
    }

}