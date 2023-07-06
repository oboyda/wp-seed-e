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
            return null;
        }

        $props_config = $type_object->get_props_config();

        foreach($fields as $key => $field)
        {
            if(!isset($field))
            {
                continue;
            }

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

    static function validateTypeProps($type_class__props_config, $fields=[], $include_props=[])
    {
        $result = [
            'fields' => [],
            'error_fields' => [],
            'errors' => []
        ];

        $props_config = is_array($type_class__props_config) ? $type_class__props_config : (is_string($type_class__props_config) ? self::getTypePropsConfig() : []);

        if(!empty($props_config))
        {
            foreach($props_config as $key => $prop_config)
            {
                if(!empty($include_props) && !in_array($key, $include_props)){
                    continue;
                }

                $type = isset($prop_config['type']) ? $prop_config['type'] : 'text';
                $validate = in_array($type, ['file', 'attachment']) ? 'file' : ( isset($prop_config['validate']) ? $prop_config['validate'] : (isset($prop_config['cast']) ? $prop_config['cast'] : 'text') );
                $required = isset($prop_config['required']) ? $prop_config['required'] : false;

                $value = isset($fields[$key]) ? $fields[$key] : null;

                $skip_add_field = false;

                /* 
                Add to errors if required and empty
                -------------------------
                */
                if($required && empty($value))
                {
                    $result['error_fields'][] = $key;
                }

                switch($validate)
                {
                    case 'email':

                        if(empty($value))
                        {
                            break;
                        }

                        if(!filter_var($value, FILTER_VALIDATE_EMAIL))
                        {
                            $result['error_fields'][] = $key;
                        }
                        break;

                    case 'file':

                        if(empty($value))
                        {
                            // Do not create index in $result['fields'] 
                            // as an empty array to prevent deleting attachments
                            $skip_add_field = true;

                            break;
                        }

                        foreach($value as $i => $file)
                        {
                            /* 
                            Check server errors
                            -------------------------
                            */
                            if(!empty($file['error']))
                            {
                                $result['error_fields'][] = $key;
                                $result['errors'][] = apply_filters('wpseed_file_error_upload_failed', sprintf(__('%s failed to upload', 'wpseed'), $file['name']), $file, $key, $prop_config);
                            }

                            /* 
                            Validate type
                            -------------------------
                            */
                            if(isset($prop_config['file_types']) && !in_array($file['type'], $prop_config['file_types']))
                            {
                                $result['error_fields'][] = $key;
                                $result['errors'][] = apply_filters('wpseed_file_error_file_type', sprintf(__('File type %1$s is not allowed for %2$s.', 'wpseed'), $file['type'], $file['name']), $file, $key, $prop_config);
                            }

                            /* 
                            Validate size
                            -------------------------
                            */
                            if(isset($prop_config['file_max_size']) && $file['size'] > $prop_config['file_max_size'])
                            {
                                $result['error_fields'][] = $key;
                                $result['errors'][] = apply_filters('wpseed_file_error_file_size', sprintf(__('File %1$s exceeds the maximum allowed file size of %2$d.', 'wpseed'), $file['name'], $prop_config['file_max_size']), $file, $key, $prop_config);
                            }
                        }

                        break;
                }

                if(!$skip_add_field)
                {
                    $result['fields'][$key] = $value;
                }
            }
        }

        $result['error_fields'] = array_unique($result['error_fields']);

        return $result;
    }
}
