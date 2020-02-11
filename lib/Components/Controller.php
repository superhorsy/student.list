<?php


namespace App\Components;


abstract class Controller

{
    //Контроль доступа
    const ACCESS_ALL = 'all_users';

    /**
     *Checks access for action
     */
    public function hasAccess():bool {

        $result = false;

        if (method_exists($this,'access')) {
            $action = Router::getInstance()->getAction();
            if (array_key_exists(self::ACCESS_ALL,$this->access())) {
                $result = in_array($action,$this->access()[self::ACCESS_ALL]) ? true : false;
            }
        }

        return $result;
    }
}