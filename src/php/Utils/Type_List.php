<?php 

namespace WPSEEDE\Utils;

class Type_List 
{
    const PER_PAGE = 10;

    static function getItems($q_args, $type_class=null)
    {
        // Parse args
        $_q_args = self::parseQueryArgs($q_args, $type_class);

        // Query
        $wp_query = new \WP_Query($_q_args);

        // Return results
        return [
            'items' => ($_q_args['fields'] == 'ids') ? $wp_query->posts : Type::getTypes($wp_query->posts, $type_class),
            'items_total' => $wp_query->found_posts
        ];
    }

    static function getLegalQueryArgs()
    {
        return [
            'post_type' => 'post',
            'post_status' => 'publish',
            'paged' => 1,
            'posts_per_page' => self::PER_PAGE,
            'meta_query' => [],
            'tax_query' => [],
            'posts__in' => [],
            'posts__not_in' => [],
            'author' => 0,
            'meta_key' => '',
            'meta_type' => '',
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'fields' => 'all'
        ];
    }

    static function parseQueryArgs($q_args, $type_class=null)
    {
        $q_args_legal = self::getLegalQueryArgs();

        $post_type = isset($q_args['post_type']) ? $q_args['post_type'] : $q_args_legal['post_type'];

        $props_config = Type::getTypePropsConfig($type_class);

        foreach($q_args as $key => $q_arg)
        {
            if(isset($q_args_legal[$key]))
            {
                if(is_array($q_args_legal[$key]) && is_array($q_arg))
                {
                    $q_args_legal[$key] = array_merge($q_args_legal[$key], $q_arg);
                }
                else if(!empty($q_arg))
                {
                    $q_args_legal[$key] = $q_arg;
                }
            }
            else if(isset($props_config[$key]))
            {
                $prop_config = $props_config[$key];

                $sys_key = isset($prop_config['sys_key']) ? $prop_config['sys_key'] : $key;
                $type = isset($prop_config['type']) ? $prop_config['type'] : 'meta';
                $q = isset($prop_config['q']) ? $prop_config['q'] : [];
                $value = (empty($q_arg) && isset($q['default'])) ? $q['default'] : $q_arg;
    
                if(empty($value))
                {
                    continue;
                }
    
                switch($type)
                {
                    case 'meta':
                        $q_args_legal['meta_query'][] = [
                            'key' => $sys_key,
                            'value' => $value,
                            'type' => isset($q['type']) ? $q['type'] : 'CHAR',
                            'compare' => isset($q['compare']) ? $q['compare'] : '='
                        ];
                    break;
                    case 'taxonomy':
                        $field = isset($q['field']) ? $q['field'] : 'term_id';
                        if(!is_array($value))
                        {
                            $value = [$value];
                        }
                        $q_args_legal['tax_query'][] = [
                            'taxonomy' => $sys_key,
                            'field' => $field,
                            'terms' => ($field === 'term_id') ? Base::parseArrInts($value) : $value,
                            'operator' => isset($q['operator']) ? $q['operator'] : 'IN'
                        ];
                    break;
                }
            }
        }

        if(count($q_args_legal['meta_query']) > 1)
        {
            $q_args_legal['meta_query']['relation'] = 'AND';
        }
        if(count($q_args_legal['tax_query']) > 1)
        {
            $q_args_legal['tax_query']['relation'] = 'AND';
        }

        return Base::filterArrayEmptyVals($q_args_legal);
    }
}