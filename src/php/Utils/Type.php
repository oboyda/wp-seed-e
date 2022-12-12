<?php

namespace WPSEEDE\Utils;

use WPSEED\Req;

class Type
{
    static function getType($post, $type_class=null)
    {
        $_post = is_int($post) ? get_post($post) : $post;

        if(isset($type_class) && class_exists($type_class))
        {
            return new $type_class($_post);
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
        return class_exists($type_class) ? $type_class::_get_props_config() : [];
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
}
