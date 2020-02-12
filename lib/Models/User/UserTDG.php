<?php


namespace App\Models\User;


use App\Components\TDG;
use PDO;

class UserTDG extends TDG
{

    public function isUsernameAvailable(string $username): bool
    {
        $data = $this->connection->query("SELECT count(*) FROM `user` WHERE `username` = '$username'")->fetchColumn();

        return ($data == 0) ? true : false;
    }

    public function isEmailAvailable(string $email): bool
    {
        $data = $this->connection->query("SELECT count(*) FROM `user` WHERE `email` = '$email'")->fetchColumn();

        return ($data == 0) ? true : false;
    }

    public function findHashByUsername($username)
    {
        $sql = "SELECT `hash` FROM {$this->table} WHERE `username` = ?";
        $stmt = $this->connection->prepare($sql);
        if (!$stmt) {
            return false;
        }
        $stmt->execute(["$username"]);
        $hash = $stmt->fetchColumn(0);

        return $hash ? $hash : false;
    }

    public function getUser($idOrUsername): ?User
    {
        $sql = "SELECT `id`, `username`, `name`, `email`, `hash` FROM `user` WHERE `username` = ? OR `id` = ?";
        $stmt = $this->connection->prepare($sql);
        if ($stmt) {
            $stmt->execute(["$idOrUsername", "$idOrUsername"]);
            $user = $stmt->fetchAll(PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE, User::class);
            if ($user[0]) {
                return $user[0];

            }
        }
        return null;
    }

    public function getUserData($idOrUsername): ?array
    {
        $sql = "SELECT `id`, `username`, `name`, `email`, `hash` FROM `user` WHERE `username` = ? OR `id` = ?";
        $stmt = $this->connection->prepare($sql);
        if ($stmt) {
            $stmt->execute(["$idOrUsername", "$idOrUsername"]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                return $user;
            }
        }
        return null;
    }

}