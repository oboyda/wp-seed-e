<?php

namespace WPSEEDE\Utils;

class Base
{
    static function getLanguages()
    {
        $langs = [];
        $langs_options = function_exists('pll_the_languages') ? pll_the_languages(['echo' => false, 'raw' => true]) : [];

        if($langs_options)
        {
            foreach($langs_options as $_lang)
            {
                $lang['code'] = isset($_lang['slug']) ? $_lang['slug'] : 'en';
                $lang['locale'] = isset($_lang['locale']) ? $_lang['locale'] : $lang['code'];
                $lang['name'] = isset($_lang['name']) ? $_lang['name'] : '';
                $lang['url'] = isset($_lang['url']) ? $_lang['url'] : '';
                $lang['current'] = isset($_lang['current_lang']) ? (bool)$_lang['current_lang'] : false;

                $langs[$lang['code']] = $lang;
            }
        }

        return $langs;
    }

    static function getLanguageParam($lang_code, $param)
    {
        $languages = self::getLanguages();
        return (isset($languages[$lang_code]) && isset($languages[$lang_code][$param])) ? $languages[$lang_code][$param] : false;
    }

    static function getCurrentLanguage($as_code=true)
    {
        // Customizer fix
        if(!isset($_REQUEST['lang']) && is_admin() && isset($_GET['customize_changeset_uuid']) && Session::getCookie('lang'))
        {
            return $as_code ? Session::getCookie('lang') : self::getLanguageParam(Session::getCookie('lang'), 'locale');
        }

        $locale = function_exists('pll_current_language') ? pll_current_language('locale') : get_locale();
        $locale = str_replace('_', '-', $locale);

        return $as_code ? substr($locale, 0, 2) : $locale;
    }

    static function getDefaultLanguage($as_code=true)
    {
        $locale = function_exists('pll_default_language') ? pll_default_language('locale') : get_locale();
        $locale = str_replace('_', '-', $locale);

        return $as_code ? substr($locale, 0, 2) : $locale;
    }

    /* ------------------------------ */

    static function parseArrInts($arr)
    {
        $_arr = [];
        if(!empty($arr))
        {
            foreach($arr as $arr_item)
            {
                $_arr[] = (int)$arr_item;
            }
        }
        return $_arr;
    }

    static function checkArrayEmptyVals($arr, $include=[], $empty_compare=[])
    {
        $empty_keys = [];

        foreach((array)$arr as $k => $a)
        {
            if($include && !in_array($k, $include))
            {
                continue;
            }

            if($empty_compare)
            {
                if(in_array($a, $empty_compare, true))
                {
                    $empty_keys[] = $k;
                }
            }
            elseif(empty($a))
            {
                $empty_keys[] = $k;
            }
        }

        return $empty_keys;
    }

    static function filterArrayEmptyVals($arr, $include=[], $empty_compare=[])
    {
        $empty_keys = self::checkArrayEmptyVals($arr, $include=[], $empty_compare=[]);

        if(!$empty_keys)
        {
            return $arr;
        }

        $_arr = [];

        foreach($arr as $k => $a)
        {
            if(!in_array($k, $empty_keys))
            {
                $_arr[$k] = $a;
            }
        }

        return $_arr;
    }

    static function filterArrayInclude($arr, $incl_keys)
    {
        if(!empty($arr))
        {
            foreach(array_keys($arr) as $key)
            {
                if(!in_array($key, $incl_keys))
                {
                    unset($arr[$key]);
                }
            }
        }
        return $arr;
    }

    static function filterArrayExclude($arr, $excl_keys)
    {
        if(!empty($arr))
        {
            foreach(array_keys($arr) as $key)
            {
                if(in_array($key, $excl_keys))
                {
                    unset($arr[$key]);
                }
            }
        }
        return $arr;
    }

    static function parseArrayItems($arr, $item_default_args=[])
    {
        if(!empty($arr) && !empty($item_default_args))
        {
            foreach($arr as $i => $a)
            {
                $arr[$i] = wp_parse_args($a, $item_default_args);
            }
        }

        return $arr;
    }

    static function castVal($val, $cast)
    {
        switch($cast)
        {
            case 'int':
            case 'integer':
                $val = intval($val);
            break;
            case 'float':
            case 'floatval':
                $val = floatval($val);
            break;
            case 'bool':
            case 'boolean';
                $val = boolval($val);
            break;
            case 'str':
            case 'string';
                $val = strval($val);
            break;
        }

        return $val;
    }

    static function castVals($vals, $casts=[])
    {
        foreach($vals as $key => $val)
        {
            if(isset($casts[$key]))
            {
                $vals[$key] = self::castVal($val, $casts[$key]);
            }
        }
        return $vals;
    }

    /* ------------------------------ */
    
    static function getTermData($field, $term, $taxonomy)
    {
        $wp_term = is_int($term) ? get_term($term, $taxonomy) : get_term_by('slug', $term, $taxonomy);

        if(is_a($wp_term, 'WP_Term') && isset($wp_term->$field))
        {
            return $wp_term->$field;
        }

        return null;
    }

    static function makeTermsHierarchy($term_ids, $taxonomy, $implode_conf=[])
    {
        $terms_h = [];

        if($term_ids)
        {
            $terms = get_terms([
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
                'include' => $term_ids
            ]);

            if(!is_wp_error($terms) && $terms)
            {
                $c = 0;
                $terms_i = [];
                while((count($terms_i) <= count($terms)) && $c < 100)
                {
                    foreach($terms as $i => $term)
                    {
                        if(!in_array($term->term_id, $terms_i))
                        {
                            self::makeTermsHierarchyFindParent($terms_h, $terms_i, $term);
                        }
                    }
                    $c++;
                }
            }
        }

        if(!empty($implode_conf))
        {
            $walk_args = [
                'conf' => wp_parse_args($implode_conf, [
                    'implode' => true,
                    'term_key' => 'name',
                    'skip_top' => false,
                    'sep' => ', ',
                    'reverse' => false
                ]), 
                'implode_parts' => []
            ];

            array_walk_recursive($terms_h, function($item, $key) use (&$walk_args){

                if(is_a($item, 'WP_Term'))
                {
                    if($walk_args['conf']['skip_top'] && !$item->parent)
                    {
                        return;
                    }

                    $term_key = $walk_args['conf']['term_key'];

                    if(isset($item->$term_key))
                    {
                        $walk_args['implode_parts'][] = $item->$term_key;
                    }
                }

            }, $walk_args);

            if($walk_args['implode_parts'] && $walk_args['conf']['reverse'])
            {
                $walk_args['implode_parts'] = array_reverse($walk_args['implode_parts']);
            }

            if($walk_args['conf']['implode'])
            {
                return implode($walk_args['conf']['sep'], $walk_args['implode_parts']);
            }

            return $walk_args['implode_parts'];
        }

        return $terms_h;
    }

    static function makeTermsHierarchyFindParent(&$terms_h, &$terms_i, $term)
    {
        $p_key = 'term_' . $term->parent;
        $c_key = 'term_' . $term->term_id;

        if(!$term->parent)
        {
            //Top level, no parent
            $terms_h[$c_key] = [
                'term' => $term,
                'children' => []
            ];

            $terms_i[] = $term->term_id;
        }
        elseif($terms_h)
        {
            foreach($terms_h as $key => &$term_h)
            {
                if($p_key === $key)
                {
                    //Parent found
                    $term_h['children'][$c_key] = [
                        'term' => $term,
                        'children' => []
                    ];

                    $terms_i[] = $term->term_id;
                }
                else
                {
                    self::makeTermsHierarchyFindParent($term_h['children'], $terms_i, $term);
                }
            }
        }
    }

    static function getTermSysName($term_id)
    {
        return get_term_meta($term_id, 'sys_name', true);
    }

    static function getTermBySysName($sys_name, $taxonomy, $fields='all')
    {
        $q_args = [
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'number' => 1,
            'meta_query' => [
                [
                    'key' => 'sys_name',
                    'value' => $sys_name,
                    'compare' => '='
                ]
            ],
            'fields' => $fields
        ];
        $terms = get_terms($q_args);

        return isset($terms[0]) ? $terms[0] : false;
    }

    /* ------------------------------ */
    
    /*
    @param string $cast integer|float
    */
    static function getMetaValsRange($meta_key, $as_string=false, $cast='integer')
    {
        global $wpdb;

        $range = [
            'min' => $wpdb->get_var($wpdb->prepare("SELECT MIN(CAST(meta_value AS DECIMAL)) FROM $wpdb->postmeta WHERE meta_key=%s", $meta_key)),
            'max' => $wpdb->get_var($wpdb->prepare("SELECT MAX(CAST(meta_value AS DECIMAL)) FROM $wpdb->postmeta WHERE meta_key=%s", $meta_key))
        ];

        switch($cast)
        {
            case 'integer':
                $range['min'] = intval($range['min']);
                $range['max'] = intval($range['max']);
                break;
            case 'float':
                $range['min'] = floatval($range['min']);
                $range['max'] = floatval($range['max']);
                break;
        }

        return $as_string ? $range['min'] . '-' . $range['max'] : $range;
    }

    /*
    @param string $cast integer|float
    */
    static function getMetaValsUnique($meta_key, $cast='integer', $as_options=false)
    {
        global $wpdb;

        $vals = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key=%s", $meta_key));

        if($vals)
        {
            foreach($vals as &$val)
            {
                switch($cast)
                {
                    case 'integer':
                        $val = intval($val);
                        break;
                    case 'float':
                        $val = floatval($val);
                        break;
                }
            }

            if(in_array($cast, ['integer', 'float']))
            {
                sort($vals, SORT_NUMERIC);
            }
            else{
                sort($vals, SORT_STRING);
            }

            if($as_options)
            {
                $options = [];
                foreach($vals as $val)
                {
                    $options[] = [
                        'value' => $val,
                        'name' => $val
                    ];
                }
                $vals = $options;
            }
        }

        return $vals;
    }

    /* ------------------------------ */

    static function genId($length=10, $pref='id_')
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz1234567890';

        if($length > strlen($chars))
        {
            $length = strlen($chars);
        }

        return $pref . substr(str_shuffle($chars), 0, $length);
    }

    static function genHash($length=20)
    {
        $hash = '';
        $nums = '0123456789';
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        $hash = $nums . $chars;
        $hash = str_shuffle($hash);
        $hash = substr($hash, 0, $length);

        return $hash;
    }
    
    static function isAdmin()
    {
        return (is_admin() && (!wp_doing_ajax() || isset($_POST['block'])));
    }

    /*
    * Select options
    * -------------------------
    */

    static function getPostSelectOptions($post_type='page', $args=[], $empty_label='')
    {
        $options = [];

        if($empty_label)
        {
            $options[0] = $empty_label;
        }

        $args = wp_parse_args($args, [
            'post_type' => $post_type,
            'posts_per_page' => 100,
            'post_status' => 'publish',
            'orderby' => 'title',
            'order' => 'ASC'
        ]);

        $posts_q = new \WP_Query($args);

        if($posts_q->posts)
        {
            foreach($posts_q->posts as $post)
            {
                $options[$post->ID] = $post->post_title;
            }
        }

        return $options;
    }

    static function getTermSelectOptions($taxonomy, $field='term_id', $args=[])
    {
        $options = [];

        $args = wp_parse_args($args, [

            'taxonomy' => $taxonomy,
            'meta_key' => '',
            'meta_value' => '',
            'meta_parent_term_id' => 0,
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false
        ]);

        if($args['meta_parent_term_id'])
        {
            $args['meta_key'] = 'parent_term_id';
            $args['meta_value'] = $args['meta_parent_term_id'];
        }
        unset($args['meta_parent_term_id']);

        $terms = get_terms($args);

        if(!is_wp_error($terms))
        {
            foreach($terms as $term)
            {
                $options[$term->$field] = $term->name;
            }
        }
        
        return $options;
    }

    /* ------------------------------ */

    static function slugToCamelCase($slug)
    {
        $slug = trim($slug);

        if(empty($slug))
        {
            return $slug;
        }

        $slug_parts = explode('-', $slug);

        array_walk($slug_parts, function(&$part){
            $part = ucfirst($part);
        });

        return implode('', $slug_parts);
    }

    /* ------------------------------ */

    static function getWeekdays($slugs=false)
    {
        $weekdays = [
            'mon' => __('Monday', 'wpseede'),
            'tue' => __('Tuesday', 'wpseede'),
            'wed' => __('Wednesday', 'wpseede'),
            'thu' => __('Thursday', 'wpseede'),
            'fri' => __('Friday', 'wpseede'),
            'sat' => __('Saturday', 'wpseede'),
            'sun' => __('Sunday', 'wpseede')
        ];

        return $slugs ? array_keys($weekdays) : $weekdays;
    }

    /* ------------------------------ */

    static function getGlobalPostId()
    {
        global $post;
        return isset($_POST['post_id']) ? (int)$_POST['post_id'] : (isset($post) ? $post->ID : 0);
    }

    static function getBlockId($block)
    {
        $parsed_block = (is_a($block, 'WP_Block') && isset($block->parsed_block)) ? $block->parsed_block : $block;

        return isset($parsed_block['attrs']) ? hash('md5', json_encode($parsed_block['attrs'])) : '';
    }

    static function getPostBlockData($block_id, $post_id=null)
    {
        global $post;

        $_post = $post_id ? get_post($post_id) : (isset($post) ? $post : get_post(self::getGlobalPostId()));
        $post_content = is_a($_post, 'WP_Post') ? $_post->post_content : '';
        $post_blocks = $post_content ? parse_blocks($post_content) : [];

        $data = null;

        foreach($post_blocks as $post_block)
        {
            $_block_id = self::getBlockId($post_block);

            if(
                $_block_id === $block_id && 
                isset($post_block['attrs']) && 
                isset($post_block['attrs']['data'])
            ){
                $data = $post_block['attrs']['data'];
            }
        }

        return $data;
    }

    static function stripBlockDataFieldsPrefixes($data, $context_name='')
    {
        $_data = [];

        if(!empty($data))
        {
            foreach($data as $key => $field)
            {
                $_key = $key;
                
                if($context_name && strpos($key, $context_name) === 0)
                {
                    $key_parts = explode('__', $key);
                    $l = count($key_parts)-1;
                    $_key = $key_parts[$l];
                }
                
                if(!isset($_data[$_key]))
                {
                    $_data[$_key] = $field;
                }
            }
        }

        return $_data;
    }

    /* ------------------------------ */

    static function getUploadBaseDir()
    {
        $upload_dir = wp_upload_dir();
        return isset($upload_dir['basedir']) ? $upload_dir['basedir'] : false;
    }

    static function getUploadBaseUrl()
    {
        $upload_dir = wp_upload_dir();
        return isset($upload_dir['baseurl']) ? $upload_dir['baseurl'] : false;
    }

    /* ------------------------------ */

    static function setWpFileSystem()
    {
        global $wp_filesystem;

        if(!isset($wp_filesystem))
        {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            if(function_exists('WP_Filesystem'))
            {
                WP_Filesystem();
            }
        }
    }

    /* ------------------------------ */

    static function debugToFile($debug, $file_name='__debug.txt', $append=false)
    {
        $debug_path = ABSPATH . $file_name;
        $debug_html = is_array($debug) ? print_r($debug, true) : $debug;
    
        if($append)
        {
            file_put_contents($debug_path, $debug_html, FILE_APPEND);
        }
        else{
            file_put_contents($debug_path, $debug_html);
        }
    }

    /* ------------------------------ */

    static function addStartSlash($str)
    {
        if($str == '/')
        {
            return $str;
        }
        if(substr($str, 0, 1) !== '/')
        {
            $str = '/' . $str;
        }
        return $str;
    }
    static function addEndSlash($str)
    {
        if($str == '/')
        {
            return $str;
        }
        if(substr($str, strlen($str)-1, strlen($str)) !== '/')
        {
            $str = $str . '/';
        }
        return $str;
    }
    static function addStartEndSlashes($str)
    {
        return self::addEndSlash(self::addStartSlash($str));
    }

    static function removeStartSlash($str)
    {
        if(substr($str, 0, 1) == '/')
        {
            $str = substr($str, 1, strlen($str));
        }
        return $str;
    }
    static function removeEndSlash($str)
    {
        if(substr($str, strlen($str)-1, strlen($str)) == '/')
        {
            $str = substr($str, 0, strlen($str)-1);
        }
        return $str;
    }
    static function removeStartEndSlashes($str)
    {
        return self::removeEndSlash(self::removeStartSlash($str));
    }

    /* ------------------------------ */

    static function trimContent($string, $length=100, $more_str='...', $more_url='')
    {
        if(empty($string) || $length >= strlen($string)){
            return $string;
        }

        $string_trimmed = '';

        $string_parts = explode(' ', $string);
        foreach($string_parts as $string_part){

            $_string_trimmed = $string_trimmed . $string_part . ' ';

            if(strlen($_string_trimmed) > $length){
                break;
            }else{
                $string_trimmed = $_string_trimmed;
            }
        }

        $string_trimmed = trim($string_trimmed);

        if($more_str){
            if($more_url){
                $string_trimmed .= '<a href="'.$more_url.'">';
            }
            $string_trimmed .= $more_str;
            if($more_url){
                $string_trimmed .= '</a>';
            }
        }

        return $string_trimmed;
    }

    /* ------------------------------ */

    static function getIconHtml($classes)
    {
        $classes = is_string($classes) ? explode(' ', $classes) : $classes;
        $classes = array_merge((array)$classes, ['app-icon']);

        if(!in_array('bi', $classes) && !in_array('icon-bg', $classes)){
            $classes[] = 'icon-bg';
        }

        return '<i class="' . implode(' ', $classes) . '"></i>';
    }

    // -------------------------

    /*
    @param string|array $path
    @param object|array $val

    @return mixed
    -------------------------
    */
    static function getObjectPath($path, $val)
    {
        $_path = is_array($path) ? $path : explode('.', $path);
        $_val = $val;
        foreach($_path as $p){
            if(is_array($_val) && isset($_val[$p])){
                $_val = $_val[$p];
            }
            elseif(is_object($_val) && isset($_val->$p)){
                $_val = $_val->$p;
            }else{
                $_val = null;
            }
        }
        return $_val;
    }

    /*
    @param string|array $path
    @param object|array $val

    @return bool
    -------------------------
    */
    static function hasObjectPath($path, $val)
    {
        $_val = self::getObjectPath($path, $val);
        return isset($_val);
    }
}