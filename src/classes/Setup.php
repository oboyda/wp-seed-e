<?php 

namespace WPSEEDE;

class Setup 
{
    protected $args;

    protected $plugin_name;
    protected $name;
    protected $textdom;
    protected $base_dir;
    protected $base_url;
    protected $version;

    var $settings;

    public function __construct($args)
    {
        $this->args = wp_parse_args($args, [
            'plugin_name' => 'WPSEEDE Plugin',
            'name' => 'wpseed',
            'textdom' => 'wpseed',
            'base_dir' => __DIR__,
            'base_dir_url' => plugins_url('', __FILE__),
            'version' => '1.0.0',

            'deps' => [
                // 'woocommerce/woocommerce.php'
            ],

            'include_files' => [
                // 'src/php/utils.php',
                // 'src/php/scripts.php',
                // 'src/php/acf-blocks.php',
                // 'src/php/acf-fields.php'
            ],

            'setup_theme' => true,
            'theme_menus' => [
                'top' => __('Top menu', 'wpseede'),
                'primary' => __('Primary menu', 'wpseede')
            ],
            'theme_image_sizes' => [
                'medium' => ['width' => 800, 'height' => 500, 'crop' => true],
                'large' => ['width' => 1200, 'height' => 750, 'crop' => true]
            ],
            'theme_thumbnail_width' => 600,
            'theme_thumbnail_height' => 375,
            'theme_logo_width' => 300,
            'theme_logo_height' => 100
        ]);

        $this->plugin_name = $this->args['name'];
        $this->name = $this->args['name'];
        $this->textdom = $this->args['textdom'];
        $this->base_dir = $this->args['base_dir'];
        $this->base_url = $this->args['base_url'];
        $this->version = $this->args['version'];

        add_action('plugins_loaded', [$this, 'initLoad'], 100);
    }

    public function initLoad()
    {
        $deps = new \WPSEED\Deps($this->args['deps'], [
            'plugin_name' => $this->plugin_name
        ]);

        if($deps->check())
        {
            $this->loadFiles();
            $this->loadModules();

            add_action('init', [$this, 'setTextDomain']);
            add_action('init', [$this, 'initSettings']);
    
            if($this->args['setup_theme'])
            {
                add_action('after_setup_theme', [$this, 'addThemeMenus']);
                add_action('after_setup_theme', [$this, 'addThemeSupport']);
                add_action('after_setup_theme', [$this, 'addThemeImageSizes']);
            }
        }
    }

    public function loadFiles()
    {
        if($this->args['include_files'])
        {
            foreach($this->args['include_files'] as $file)
            {
                $_file = (strpos($inc_file, '/') === 0) ? $file : $this->base_dir . '/' . $file;

                if(file_exists($_file))
                {
                    require_once $_file;
                }
            }
        }
    }

    public function loadModules()
    {
        foreach(wpseed_get_dir_files($this->base_dir . '/mods', true, false) as $dir)
        {
            if(!is_dir($dir)) continue;

            $mod_index_file = $dir . '/index.php';
            if(file_exists($mod_index_file))
            {
                require_once $mod_index_file;
            }
        }
    }

    public function setTextDomain()
    {
        load_plugin_textdomain($this->textdom, false, plugin_basename($this->base_dir) . '/languages');
    }

    public function initSettings()
    {
        $this->settings = new \WPSEEDE\Settings([
            'opts_prefix' => $this->args['name'] . '_'
        ]);
    }

    public function addThemeMenus()
    {
        if($this->args['theme_menus'])
        {
            register_nav_menus($this->args['theme_menus']);
        }
    }

    public function addThemeSupport()
    {
        add_theme_support('title-tag');
        
        add_theme_support('post-thumbnails');
        set_post_thumbnail_size($this->args['theme_thumbnail_width'], $this->args['theme_thumbnail_height']);
    
        add_theme_support(
            'custom-logo',
            array(
                'width' => $this->args['theme_logo_width'],
                'height' => $this->args['theme_logo_height'],
                'flex-width' => true,
                'flex-height' => true,
                'unlink-homepage-logo' => true
            )
        );
    
        add_theme_support('customize-selective-refresh-widgets');
    }

    public function addThemeImageSizes()
    {
        if($this->args['theme_image_sizes'])
        {
            foreach($this->args['theme_image_sizes'] as $name => $image_size)
            {
                $image_size = wp_parse_args($image_size, [
                    'width' => 100,
                    'height' => 100,
                    'crop' => true
                ]);
                add_image_size($name, $image_size['width'], $image_size['height'], $image_size['crop']);
            }
        }
    }
}