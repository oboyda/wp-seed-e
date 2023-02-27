<?php

namespace WPSEEDE;

use WPSEEDE\Utils\Base;

class Settings 
{
    protected $args;

    var $context_name;
    var $prefix;

    public function __construct($args)
    {
        $this->args = wp_parse_args($args, [
            'context_name' => 'wpseede'
        ]);

        $this->context_name = $this->args['context_name'];
        $this->prefix = $this->context_name . '_';
    }

    public function getOption($name, $default=null)
    {
        $settings = new \WPSEED\Settings([
            'prefix' => $this->prefix,
            'render_fields' => false
        ]);

        $opt = $settings->get_option($name);

        return (empty($opt) && isset($default)) ? $default : $opt;
    }

    public function getThemeOption($name, $default=null, $set_lang=true)
    {
        if($set_lang)
        {
            $name .= '_' . Base::getCurrentLanguage();
        }
        
        $option = get_theme_mod($name);

        return (empty($option) && isset($default)) ? $default : $option;
    }
}
