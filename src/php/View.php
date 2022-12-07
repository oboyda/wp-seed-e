<?php

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View extends \WPSEED\View 
{
    const CONTEXT_NAME = 'wpseede';

    protected $orig_args;
    protected $child_parts;

    protected $data;

    public function __construct($args, $default_args=[])
    {
        $this->orig_args = $args;
        $this->child_parts = [];

        parent::__construct($args, wp_parse_args($default_args, [

            'id' => $this->getField('id', '', $args),
            'block_id' => '',
            'html_class' => $this->getField('html_class', '', $args),
            'hide' => (bool)$this->getField('hide', false, $args),
            'hide_mobile' => (bool)$this->getField('hide_mobile', false, $args),
            'hide_desktop' => (bool)$this->getField('hide_desktop', false, $args),
            'top_level' => (bool)$this->getField('top_level', false, $args),
            'padding_bottom' => $this->getField('padding_bottom', '', $args),
            'margin_bottom' => $this->getField('margin_bottom', '', $args),
            'container_class' => $this->getField('container_class', 'container-lg', $args),
            'data' => []
        ]));

        if(!isset($this->data))
        {
            $this->data = $this->args['data'];
        }

        $this->setHtmlClass();
    }

    public function setChildPart($name, $html)
    {
        $this->child_parts[$name] = $html;
    }
    public function getChildPart($name)
    {
        return isset($this->child_parts[$name]) ? $this->child_parts[$name] : '';
    }
    public function getChildParts()
    {
        return $this->child_parts;
    }

    protected function setHtmlClass()
    {
        $this->addHtmlClass(static::CONTEXT_NAME);

        if($this->args['html_class'])
        {
            $this->addHtmlClass($this->args['html_class']);
        }

        if($this->args['top_level'])
        {
            $this->addHtmlClass('section');
        }

        if($this->args['padding_bottom'] !== '')
        {
            $pb = ($this->args['padding_bottom'] === 'none') ? '0' : $this->args['padding_bottom'];
            $this->addHtmlClass('pb-' . $pb);
        }
        
        if($this->args['margin_bottom'] !== '')
        {
            $mb = ($this->args['margin_bottom'] === 'none') ? '0' : $this->args['margin_bottom'];
            $this->addHtmlClass('mb-' . $mb);
        }

        if($this->args['hide_mobile'])
        {
            $this->addHtmlClass('hide-mobile');
        }
        if($this->args['hide_desktop'])
        {
            $this->addHtmlClass('hide-desktop');
        }
    }

    public function setArgsData($args)
    {
        $this->data = (is_array($args) && isset($args['data'])) ? $args['data'] : [];
    }
    
    public function getField($name, $default=null, $args=[])
    {
        $_name = static::CONTEXT_NAME . '__' . $this->getName(true) . '__' . $name;
        $post_id = Utils_Base::getGlobalPostId();

        if(!empty($args['block_id']))
        {
            if(empty($args['data']))
            {
                $this->data = Utils_Base::getPostBlockData($args['block_id'], $post_id);
            }
            else{
                $this->data = $args['data'];
            }
        }
        
        $field = null;

        if(isset($this->data[$_name]))
        {
            $field = $this->data[$_name];
        }
        elseif(function_exists('get_field'))
        {
            $field = get_field($_name, $post_id);
        }
        
        return (empty($field) && isset($default)) ? $default : $field;
    }

    public function getGroupField($group, $name, $default=null, $args=null)
    {
        $field = $this->getField($name, null, $args);
        
        return (is_array($field) && isset($field[$name])) ? $field[$name] : $default;
    }

    protected function getAdminPostId()
    {
        return (is_admin() && isset($_GET['post'])) ? (int)$_GET['post'] : ((is_admin() && isset($_POST['post_id'])) ? (int)$_POST['post_id'] : 0);
    }
    
    public function encodeFieldToJson($field_name)
    {
        echo json_encode(is_array($this->$field_name) ? $this->$field_name : []);
    }
    
    public function getViewTag()
    {
        return $this->has_top_level() ? 'section' : 'div';
    }
    
    public function getContainerTagOpen($size='')
    {
        $size_class = !empty($size) ? 'container-' . $size : 'container';
        return $this->has_top_level() ? '<div class="' . $size_class . '">' : '';
    }
    public function openContainer($size='')
    {
        echo $this->getContainerTagOpen($size);
    }
    
    public function getContainerTagClose()
    {
        return $this->has_top_level() ? '</div><!-- .container -->' : '';
    }
    public function closeContainer()
    {
        echo $this->getContainerTagClose();
    }

    static function implodeAtts($atts)
    {
        $_atts = [];

        if(!empty($atts) && is_array($atts))
        {
            foreach($atts as $att_name => $att)
            {
                $att = is_string($att) ? trim($att) : $att;
                $att = is_array($att) ? implode(' ', $att) : $att;

                if($att !== '')
                {
                    $_atts[] = $att_name . '="' . $att . '"';
                }
            }
        }

        return $_atts ? implode(' ', $_atts) : '';
    }

    static function getAttachmentImage($attachment_id, $size='full')
    {
        return $attachment_id ? wp_get_attachment_image($attachment_id, $size) : '';
    }

    static function getAttachmentImageSrc($attachment_id, $size='full')
    {
        $image_src = $attachment_id ? wp_get_attachment_image_src($attachment_id, $size) : [];
        return ($image_src && isset($image_src[0])) ? $image_src[0] : '';
    }

    static function getImageHtml($image, $args=[])
    {
        $args = wp_parse_args($args, [
            'size' => 'full', 
            'rel_class' => 'rect-150-100', 
            'fit' => 'cover', 
            'alt' => ''
        ]);

        $html  = '<div class="img-resp img-' . $args['fit'] . ' ' . $args['rel_class'] . '">';
            $html .= is_int($image) ? self::getAttachmentImage($image, $args['size']) : '<img alt="' . $args['alt'] . '" src="' . $image . '" />';
        $html .= '</div>';

        return $html;
    }

    static function getBgImageHtml($image, $args=[])
    {
        $html = '';

        $image_src = is_int($image) ? self::getAttachmentImageSrc($image) : $image;

        $args = wp_parse_args($args, [
            'size' => 'full', 
            'rel_class' => 'rect-150-100', 
            'fit_class' => 'cover', 
            'atts' => []
        ]);
        $args['atts'] = wp_parse_args($args['atts'], [
            'class' => '',
            'style' => ''
        ]);
        $args['atts']['class'] .= ' bg-img bg-img-' . $args['fit_class'] . ' ' . $args['rel_class'];

        if($image_src)
        {
            $args['atts']['style'] = "background-image: url(" . $image_src . ")";

            $html = '<div ' . self::implodeAtts($args['atts']) . '></div>';
        }

        return $html;
    }

    public function getAdminEditButton()
    {
        if((!wp_doing_ajax() && is_admin()) || (wp_doing_ajax() && isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], '/wp-admin') !== false))
        {
            echo '<div class="block-edit-handle">';
                echo '<span class="edit-handle">' . $this->getName() . '</span>';
            echo '</div>';
        }
    }

    public function renderItemsCols($items, $cols_num=2, $col_size='lg')
    {
        $html = '';
        if(is_array($items) && !empty($items))
        {
            $col_class = 'col-' . $col_size . '-' . (12/$cols_num);
            foreach(array_chunk($items, $cols_num) as $items_row)
            {
                $html .= '<div class="row">';
                foreach($items_row as $item)
                {
                    $html .= '<div class="' . $col_class . '">';
                        $html .= $item;
                    $html .= '</div><!-- .col -->';
                }
                $html .= '</div><!-- .row -->';
            }
        }
        return $html;
    }
}
