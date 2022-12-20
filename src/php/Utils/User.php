<?php

namespace WPSEEDE\Utils;

class User
{
    static function getUser($user, $type_class)
    {
        return (is_int($user) || is_a($user, 'WP_User')) ? (class_exists($type_class) ? new $type_class($user) : null) : $user;
    }

    static function getCurrentUser($type_class)
    {
        return self::getUser(get_current_user_id(), $type_class);
    }

    static function getCurrentUserRole($type_class)
    {
        $type_user = self::getCurrentUser($type_class);
        return isset($type_user) ? $type_user->getRole() : 'public';
    }
}