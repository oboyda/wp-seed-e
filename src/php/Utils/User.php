<?php

namespace WPSEEDE\Utils;

class User
{
    static function getUser($user, $type_class)
    {
        // return (is_int($user) || is_a($user, 'WP_User')) ? (class_exists($type_class) ? new $type_class($user) : null) : $user;
        return is_a($user, $type_class) ? $user : (class_exists($type_class) ? new $type_class($user) : null);
    }

    static function getCurrentUser($type_class)
    {
        return is_user_logged_in() ? self::getUser(get_current_user_id(), $type_class) : null;
    }

    static function getCurrentUserRole($type_class)
    {
        $type_user = self::getCurrentUser($type_class);
        return isset($type_user) ? $type_user->getRole() : 'public';
    }
}