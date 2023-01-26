<?php

namespace WPSEEDE\Utils;

use WPSEED\Req;

class Type
{
    static function isUserTypeClass($type_class)
    {
        return (strpos($type_class, 'User') !== false);
    }

    static function getType($post_user, $type_class='\WP_Post')
    {
        $_post_user = (is_int($post_user) && !empty($post_user)) ? (self::isUserTypeClass($type_class) ? get_userdata($post_user) : get_post($post_user)) : $post_user;

        if(isset($type_class))
        {
            return class_exists($type_class) ? new $type_class($_post_user) : null;
        }

        return $_post_user;
    }

    static function getTypes($posts_users, $type_class='\WP_Post')
    {
        $types = [];
        foreach($posts_users as $post_user)
        {
            $types[] = self::getType($post_user, $type_class);
        }
        return $types;
    }

    static function getTypePropsConfig($type_class, $key=null, $data_key=null, $default=null)
    {
        $type_object = self::getType(null, $type_class);
        return is_a($type_object, '\WPSEED\Entity') ? $type_object->get_props_config($key, $data_key, $default) : [];
    }

    static function getTypePostType($type_class)
    {
        $type_object = self::getType(0, $type_class);
        return is_a($type_object, '\WPSEED\Post') ? $type_object->get_post_type() : 'post';
    }

    static function getTypeName($type_class)
    {
        return self::getTypePostType($type_class);
    }

    static function getTypeRequestArgs($type_class, $include=[], $check_cap=false)
    {
        $req = new Req();

        $props_config = self::getTypePropsConfig($type_class);

        $req_args = [];

        foreach($props_config as $key => $prop_config)
        {
            if(!empty($include) && !in_array($key, $include))
            {
                continue;
            }

            $query_cap = $check_cap ? (isset($prop_config['query_cap']) ? $prop_config['query_cap'] : false) : 'all';

            if(!(in_array($query_cap, ['all', 'public']) || ($query_cap && current_user_can($query_cap))))
            {
                continue;
            }

            $sanitize = isset($prop_config['input_sanitize']) ? $prop_config['input_sanitize'] : 'text';
            $value = $req->get($key, $sanitize);

            if(!empty($value))
            {
                $req_args[$key] = $value;
            }
        }

        return $req_args;
    }

    static function updateType($type_id, $type_class, $fields, $persist=true, $check_cap=true)
    {
        $type_object = is_int($type_id) ? self::getType($type_id, $type_class) : $type_id;

        if(!is_object($type_object))
        {
            return false;
        }

        $props_config = $type_object->get_props_config();

        foreach($fields as $key => $field)
        {
            $prop_config = isset($props_config[$key]) ? $props_config[$key] : null;

            if(!isset($prop_config))
            {
                continue;
            }

            $edit_cap = $check_cap ? (isset($prop_config['edit_cap']) ? $prop_config['edit_cap'] : 'edit_posts') : 'all';

            if(!($edit_cap && (in_array($edit_cap, ['all', 'public']) || current_user_can($edit_cap))))
            {
                continue;
            }

            $type_object->set_prop($key, $field);
        }

        if($persist)
        {
            $type_object->persist();
        }

        return $type_object;
    }

    static function createType($type_class, $fields, $persist=true, $check_cap=true)
    {
        return self::updateType(0, $type_class, $fields, $persist, $check_cap);
    }
}
