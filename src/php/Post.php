<?php 

namespace WPSEEDE;

class Post extends \WPSEED\Post
{
    public function __construct($post=null, $props_config=[])
    {
        parent::__construct($post, array_merge($props_config, self::_get_props_config()));
    }

    static function _get_props_config()
    {
        return [];
    }

    public function getPropConfigData($key, $data_key=null, $default=null)
    {
        $prop_config = $this->get_props_config($key);

        if(isset($data_key))
        {
            return isset($prop_config[$data_key]) ? $prop_config[$data_key] : $default;
        }

        return isset($prop_config) ? $prop_config : $default;
    }

    public function getPropOptionLabel($key, $option_value)
    {
        $options = $this->getPropConfigData($key, 'options', []);

        return isset($options[$option_value]) ? $options[$option_value] : false;
    }

    public function getId()
    {
        return $this->get_id();
    }

    public function getTitle()
    {
        return $this->get_data('post_title', '');
    }

    public function getExcerpt($autop=false)
    {
        $data = $this->get_data('post_excerpt', '');

        if($data)
        {
            if($autop)
            {
                $data = wpautop($data);
            }
        }

        return $data;
    }

    public function getContent($autop=false, $words_num=null)
    {
        $data = $this->get_data('post_content', '');

        if($data)
        {
            if(isset($words_num))
            {
                $data = wp_trim_words($data, $words_num);
            }
            if($autop)
            {
                $data = wpautop($data);
            }
        }

        return $data;
    }

    public function getLink()
    {
        return $this->get_permalink();
    }

    public function getAuthor()
    {
        return $this->get_data('post_author');
    }

    public function hasProp($key)
    {
        return $this->has_prop($key);
    }

    public function getProp($key, $default=null, $single=true)
    {
        return $this->get_prop($key, $default, $single);
    }

    public function getTerms($taxonomy, $fields='ids', $single=true)
    {
        $term_ids = $this->get_prop($taxonomy, [], false);

        if($fields === 'ids')
        {
            return $single ? (isset($term_ids[0]) ? $term_ids[0] : false) : $term_ids;
        }

        $args = [
            'taxonomy' => $taxonomy,
            'include' => $term_ids,
            'fields' => $fields
        ];

        $terms = get_terms($args);

        if(is_wp_error($terms))
        {
            return $single ? false : [];
        }

        if($single)
        {
            return isset($terms[0]) ? $terms[0] : false;
        }

        return $terms;
    }

    /*
    Images
    -------------------------
    */

    public function getFeaturedImageId()
    {
        return get_post_thumbnail_id($this->get_id());
    }
    public function getFeaturedImageSrc($size='thumbnail', $placeholder=false)
    {
        $image_id = $this->getFeaturedImageId();
        $att_image_src = $image_id ? wp_get_attachment_image_src($image_id, $size) : [];
        $image_src = ($att_image_src && isset($att_image_src[0])) ? $att_image_src[0] : '';

        return (empty($image_src) && $placeholder) ? (function_exists('wc_placeholder_img_src') ? wc_placeholder_img_src($size) : $image_src) : $image_src;
    }
    public function hasFeaturedImage()
    {
        $image_id = $this->getFeaturedImageId();
        return !empty($image_id);
    }
}
