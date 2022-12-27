<?php

namespace WPSEEDE\Utils;

use WPSEED\Req;

class Type
{
    static function getType($post, $type_class=null)
    {
        $_post = (is_int($post) && !empty($post)) ? get_post($post) : $post;

        if(isset($type_class))
        {
            return class_exists($type_class) ? new $type_class($_post) : null;
        }

        return $_post;
    }

    static function getTypes($posts, $type_class=null)
    {
        $types = [];
        foreach($posts as $post)
        {
            $types[] = self::getType($post, $type_class);
        }
        return $types;
    }

    static function getTypePropsConfig($type_class)
    {
        $type_object = self::getType(null, $type_class);
        return is_a($type_object, '\WPSEED\Entity') ? $type_object->get_props_config() : [];
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

    static function updateType($id, $type_class, $fields, $persist=true, $check_cap=false)
    {
        $type_object = self::getType($id, $type_class);

        if(!isset($type_object))
        {
            return false;
        }

        $props_config = $type_object->get_props_config();

        foreach($props_config as $key => $prop_config)
        {
            $field = isset($fields[$key]) ? $fields[$key] : null;

            if(!isset($field))
            {
                continue;
            }

            $edit_cap = $check_cap ? (isset($prop_config['edit_cap']) ? $prop_config['edit_cap'] : false) : 'all';

            if(!($edit_cap && (in_array(['all', 'public']) || current_user_can($edit_cap))))
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
