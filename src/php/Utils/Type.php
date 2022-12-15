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
        return (class_exists($type_class) && method_exists($type_class, '_get_props_config')) ? $type_class::_get_props_config() : [];
    }

    static function getTypePostType($type_class)
    {
        $type_object = self::getType(0, $type_class);
        return method_exists($type_object, 'get_type') ? $type_object->get_type() : 'post';
    }

    static function getTypeName($type_class)
    {
        return self::getTypePostType($type_class);
    }

    static function getTypeRequestArgs($type_class, $include=[])
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

            $sanitize = isset($prop_config['input_sanitize']) ? $prop_config['input_sanitize'] : 'text';
            $value = $req->get($key, $sanitize);

            if(!empty($value))
            {
                $req_args[$key] = $value;
            }
        }

        return $req_args;
    }

    static function editType($id, $type_class, $fields, $persist=true, $check_cap=true)
    {
        $type_object = self::getType($id, $type_class);

        if(!isset($type_object))
        {
            return false;
        }

        $props_config = self::getTypePropsConfig($type_class);

        foreach($fields as $key => $field)
        {
            $prop_config = isset($props_config[$key]) ? $props_config[$key] : [];

            if(empty($prop_config))
            {
                continue;
            }

            $edit_cap = $check_cap ? (isset($prop_config['edit_cap']) ? $prop_config['edit_cap'] : false) : 'all';

            if(!($check_cap == 'all' || ($check_cap && current_user_can($edit_cap))))
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
        return self::editType(0, $type_class, $fields, $persist, $check_cap);
    }
}
