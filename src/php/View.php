<?php

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View extends \WPSEED\View 
{
    protected $view_loader;

    protected $child_parts;

    protected $field_defaults;

    protected $context_name;
    protected $mod_name;

    // const CONTEXT_NAME = '';
    // const MOD_NAME = '';

    public function __construct($args=[], $args_default=[])
    {
        $this->child_parts = [];

        parent::__construct($args, array_merge([

            'id' => '',
            'block_id' => '',
            'html_class' => '',
            'hide' => false,
            'hide_mobile' => false,
            'hide_desktop' => false,
            'top_level' => false,
            'container_class' => false

        ], $args_default));

        $this->setHtmlClass();
    }

    /* ------------------------- */

    protected function setContextName($context_name)
    {
        $this->context_name = $context_name;
    }

    protected function getContextName()
    {
        // return defined('static::CONTEXT_NAME') ? static::CONTEXT_NAME : (isset($this->context_name) ? $this->context_name : '');
        return isset($this->context_name) ? $this->context_name : (defined('static::CONTEXT_NAME') ? static::CONTEXT_NAME : '');
    }

    protected function setModName($mod_name)
    {
        $this->mod_name = $mod_name;
    }

    protected function getModName($as_slug=false)
    {
        // $mod_name = defined('static::MOD_NAME') ? static::MOD_NAME : (isset($this->mod_name) ? $this->mod_name : '');
        $mod_name = isset($this->mod_name) ? $this->mod_name : (defined('static::MOD_NAME') ? static::MOD_NAME : '');

        return $as_slug ? strtolower(str_replace('_', '-', $mod_name)) : $mod_name;
    }

    /* ------------------------- */

    public function getName($include_context=true, $include_mod=true, $sep='.')
    {
        $name_parts = [];

        if($include_context && $this->getContextName())
        {
            $name_parts['context_name'] = $this->getContextName();
        }

        if($include_mod && $this->getModName())
        {
            $name_parts['mod_name'] = $this->getModName(true);
        }

        $name_parts['view_name'] = $this->getViewName();

        $name = implode($sep, $name_parts);

        return $name;
    }

    /* ------------------------- */

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

    private function setHtmlClass()
    {
        $this->addHtmlClass($this->getContextName());
        $this->addHtmlClass($this->getModName(true));

        if($this->args['html_class'])
        {
            $this->addHtmlClass($this->args['html_class']);
        }

        if($this->args['top_level'])
        {
            $this->addHtmlClass('section');
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

    static function getPostId()
    {
        return Utils_Base::getGlobalPostId();
    }

    static function getAdminPostId()
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
        $args = array_merge([
            'size' => 'full', 
            // 'rel_class' => 'rect-150-100', 
            'rel_class' => '', 
            'fit' => 'cover', 
            'alt' => ''
        ], $args);

        $cont_class = ['img-resp'];
        
        if($args['rel_class']){
            $cont_class[] = 'img-rel';
            $cont_class[] = $args['rel_class'];
        }
        if($args['fit']){
            $cont_class[] = 'img-' . $args['fit'];
        }

        $html  = '<div class="' . implode(' ', $cont_class) . '">';
            $html .= is_int($image) ? self::getAttachmentImage($image, $args['size']) : '<img alt="' . $args['alt'] . '" src="' . $image . '" />';
        $html .= '</div>';

        return $html;
    }

    static function getBgImageHtml($image, $args=[])
    {
        $html = '';

        $image_src = is_int($image) ? self::getAttachmentImageSrc($image) : $image;

        $args = array_merge([
            'size' => 'full', 
            'rel_class' => 'rect-150-100', 
            'fit' => 'cover', 
            'atts' => []
        ], $args);
        $args['atts'] = array_merge([
            'class' => '',
            'style' => ''
        ], $args['atts']);
        $args['atts']['class'] .= ' bg-img bg-img-' . $args['fit'] . ' ' . $args['rel_class'];

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

    static function getIconHtml($classes)
    {
        return Utils_Base::getIconHtml($classes);
    }

    static function parseBtnArgs($args, $pref='')
    {
        $args_default = [
            $pref.'label' => '',
            $pref.'page' => 0,
            $pref.'url' => '',
            $pref.'js_event' => '',
            $pref.'target' => '_self',
            $pref.'target_blank' => false,
            $pref.'icon_html' => ''
        ];

        if($pref && isset($args[$pref])){
            foreach($args[$pref] as $k => $v){
                $args[$pref.'_'.$k] = $v;
            }
            unset($args[$pref]);
        }

        $args = array_merge($args_default, $args);

        if(!$args[$pref.'url'] && $args[$pref.'js_event']){
            $args[$pref.'url'] = '#';
        }
        if($args[$pref.'page']){
            $args[$pref.'url'] = get_permalink((int)$args[$pref.'page']);
        }
        if($args[$pref.'target_blank']){
            $args[$pref.'target'] = '_blank';
        }

        return $args;
    }
}
