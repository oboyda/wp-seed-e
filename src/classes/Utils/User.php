<?php

namespace WPSEEDE\Utils;

class User
{
    static function getCurrentUser($type_class)
    {
        $user_id = get_current_user_id();
        return ($user_id && class_exists($type_class)) ? new $type_class($user_id) : null;
    }

    static function getCurrentUserRole($type_class)
    {
        $type_user = self::getCurrentUser($type_class);
        return isset($type_user) ? $type_user->getRole() : 'public';
    }
}