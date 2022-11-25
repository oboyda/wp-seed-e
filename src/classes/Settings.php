<?php

namespace WPSEEDE;

class Settings 
{
    protected $args;
    protected $settings;

    public function __construct($args=[])
    {
        $this->args = wp_parse_args($args, [
            'opts_prefix' => 'wpseede_'
        ]);

        $this->settings = new \WPSEED\Settings([
            'prefix' => $this->args['opts_prefix'],
            'render_fields' => false
        ]);
    }

    public function getOption($name, $default=null)
    {
        $opt = $this->settings->get_option($name);

        return (empty($opt) && isset($default)) ? $default : $opt;
    }

    public function getThemeOption($name, $default=null, $set_lang=true)
    {
        $lang = Base::getCurrentLanguage();

        if($set_lang)
        {
            $name .= '_' . $lang;
        }
        
        $option = get_theme_mod($name);

        return (empty($option) && isset($default)) ? $default : $option;
    }

    public function getLogoUrl()
    {
        $logo_id = (int)$this->getThemeOption('custom_logo', 0, false);

        $image_src = $logo_id ? wp_get_attachment_image_src($logo_id, 'full') : [];
        return ($image_src && isset($image_src[0])) ? $image_src[0] : '';
    }
}
