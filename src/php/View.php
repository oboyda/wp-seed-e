<?php

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View extends \WPSEED\View 
{
    // const CONTEXT_NAME = 'wpseede';

    protected $context_name;
    protected $view_loader;

    protected $args_ext;
    protected $child_parts;

    protected $data;
    protected $field_defaults;

    public function __construct($args=[], $args_default=[])
    {
        if(!isset($this->context_name) && defined('CONTEXT_NAME'))
        {
            $this->context_name = static::CONTEXT_NAME;
        }
        // $this->args_ext = $this->getSavedViewArgsAjax($args);
        $this->args_ext = $args;

        $this->child_parts = [];

        parent::__construct($args, wp_parse_args($args_default, [

            'id' => $this->getField('id', ''),
            'block_id' => '',
            'html_class' => $this->getField('html_class', ''),
            'hide' => $this->getField('hide', false),
            'hide_mobile' => $this->getField('hide_mobile', false),
            'hide_desktop' => $this->getField('hide_desktop', false),
            'top_level' => $this->getField('top_level', false),
            'padding_bottom' => $this->getField('padding_bottom', ''),
            'margin_bottom' => $this->getField('margin_bottom', ''),
            'container_class' => $this->getField('container_class', 'container-lg'),
            'data' => []
        ]));

        $this->setDataFields();

        $this->setHtmlClass();
    }

    protected function saveViewArgs($args=null)
    {
        if(isset($this->view_loader))
        {
            $_args = isset($args) ? $args : $this->getArgsExt();

            $_args = $this->filterArgsPublic($_args);

            $this->view_loader->saveViewArgs($this->getId(), $_args);
        }
    }

    protected function getSavedViewArgsAjax($args)
    {
        if(!wp_doing_ajax())
        {
            return $args;
        }

        $view_id = isset($args['id']) ? $args['id'] : null;

        if(isset($view_id) && isset($this->view_loader))
        {
            $args = $this->view_loader->getViewArgs($view_id);
        }

        return $args;
    }

    public function getArgsExt()
    {
        return $this->args_ext;
    }

    public function getArgsExtPublic()
    {
        return $this->filterArgsPublic($this->args_ext);
    }

    protected function filterArgsPublic($args)
    {
        return Utils_Base::filterArrayExclude($args, ['block_data']);
    }

    public function setChildPart($name, $html)
    {
        $this->child_parts[$name] = $html;
    }
    public function getChildPart($name)
    {
        return isset($this->child_parts[$name]) ? $this->child_parts[$name] : '';
    }
    public function hasChildPart($name)
    {
        return !empty($this->child_parts[$name]);
    }
    public function getChildParts()
    {
        return $this->child_parts;
    }

    protected function setDataFields()
    {
        $this->data = (!empty($this->args['block_id']) && $this->args['data']) ? Utils_Base::getPostBlockData($this->args['block_id'], Utils_Base::getGlobalPostId()) : $this->args['data'];
        
        unset($this->args['data']);

        if(!empty($this->field_defaults))
        {
            foreach($this->field_defaults as $name => $default)
            {
                if(empty($this->args[$name]))
                {
                    $this->args[$name] = $this->_getField($name, $default);
                }
            }
        }
    }

    protected function getField($name, $default=null)
    {
        // The constructor is already called, get the field
        if(isset($this->args))
        {
            return $this->_getField($name, $default);
        }

        // Save field for later use in setDataFields
        if(!isset($this->field_defaults))
        {
            $this->field_defaults = [];
        }

        $this->field_defaults[$name] = $default;
    }
    
    protected function _getField($name, $default=null)
    {
        $_name = $this->context_name . '__' . $this->getName(true) . '__' . $name;

        $post_id = $this->getPostId();

        $field = null;

        if(isset($this->data[$_name]))
        {
            $field = $this->data[$_name];
        }
        elseif(function_exists('get_field'))
        {
            $field = get_field($_name, $post_id);
        }
        else{
            $field = get_post_meta($_name, $post_id, true);
        }
        
        return (empty($field) && isset($default)) ? $default : $field;
    }

    public function getGroupField($group, $name, $default=null, $args=null)
    {
        $field = $this->getField($name, null, $args);
        
        return (is_array($field) && isset($field[$name])) ? $field[$name] : $default;
    }

    protected function setHtmlClass()
    {
        $this->addHtmlClass($this->context_name);

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

    protected function getPostId()
    {
        return Utils_Base::getGlobalPostId();
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

    public function renderItemsCols($items_html, $cols_num=2, $col_class='lg')
    {
        return $this->distributeCols($items_html, $cols_num, $col_class);
    }
}
