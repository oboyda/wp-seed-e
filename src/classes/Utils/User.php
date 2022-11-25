<?php

namespace WPSEEDE\Utils;

use WPSEEDE\Type\User as Type_User;

class User
{
    static function getCurrentUser()
    {
        $user_id = get_current_user_id();
        return $user_id ? new Type_User($user_id) : null;
    }

    static function getCurrentUserRole()
    {
        $type_user = self::getCurrentUser();
        return isset($type_user) ? $type_user->getRole() : 'public';
    }
}