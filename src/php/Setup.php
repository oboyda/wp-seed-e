<?php 

namespace WPSEEDE;

class Setup 
{
    protected $args;

    protected $plugin_name;
    protected $context_name;
    protected $namespace;
    protected $textdom;
    protected $base_dir;
    protected $base_dir_url;
    protected $version;

    var $settings_admin;
    var $settings;

    var $view_loader;

    var $script;

    public function __construct($args)
    {
        $this->parseArgs($args);

        add_action('plugins_loaded', [$this, 'initLoad'], 100);
    }

    protected function parseArgs($args=[])
    {
        if(empty($args) && isset($this->args))
        {
            return;
        }

        if(!isset($this->args))
        {
            $this->args = wp_parse_args($args, [

                'plugin_name' => 'WPSEEDE Plugin',
                'context_name' => 'wpseede',
                'namespace' => 'WPSEEDE',
                'textdom' => 'wpseede',
                'base_dir' => __DIR__,
                'base_dir_url' => plugins_url('', __FILE__),
                'version' => '1.0.0',

                'plugin_deps' => [
                    // 'woocommerce/woocommerce.php'
                ],

                'include_files' => [
                    // 'src/php/utils.php',
                    // 'src/php/scripts.php',
                    // 'src/php/acf-blocks.php',
                    // 'src/php/acf-fields.php'
                ],

                'load_modules' => [], #array, string 'all'
                
                'settings_config' => [],

                'init_scripts' => false,

                'init_theme' => false,
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
        }
        elseif($args)
        {
            $this->args = wp_parse_args($args, $this->args);
        }

        $this->plugin_name = $this->args['plugin_name'];
        $this->context_name = $this->args['context_name'];
        $this->namespace = $this->args['namespace'];
        $this->textdom = $this->args['textdom'];
        $this->base_dir = $this->args['base_dir'];
        $this->base_dir_url = $this->args['base_dir_url'];
        $this->version = $this->args['version'];
    }

    public function initLoad()
    {
        $deps = new \WPSEED\Deps($this->args['plugin_deps'], [
            'plugin_name' => $this->plugin_name
        ]);

        if($deps->check())
        {
            $this->loadFiles();
            $this->loadModules();

            add_action('init', [$this, 'setTextDomain']);

            if($this->args['settings_config'])
            {
                $this->_initSettings();
            }

            if($this->args['init_scripts'])
            {
                $this->initScripts();
            }
    
            if($this->args['init_theme'])
            {
                $this->initTheme();
            }

            $this->initViewLoader();
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
        $dir_files = wpseed_get_dir_files($this->base_dir . '/mods', true, false);
        
        if(!empty($dir_files) && !empty($this->args['load_modules']))
        {
            foreach($dir_files as $file)
            {
                if(!(
                    is_dir($file) && 
                    (
                        (is_array($this->args['load_modules']) && in_array(basename($file), $this->args['load_modules'])) || 
                        $this->args['load_modules'] == 'all'
                    )
                )){
                    continue;
                }

                $mod_index_file = $file . '/index.php';
                if(file_exists($mod_index_file))
                {
                    require_once $mod_index_file;
                }
            }
        }
    }

    public function setTextDomain()
    {
        load_plugin_textdomain($this->textdom, false, plugin_basename($this->base_dir) . '/languages');
    }

    public function initSettings($args=[])
    {
        $this->parseArgs($args);
        add_action('plugins_loaded', [$this, '_initSettings']);
    }
    public function _initSettings()
    {
        if($this->args['settings_config'])
        {
            $this->settings_admin = new \WPSEED\Settings([

                'prefix' => $this->context_name . '_',
                'menu_page' => 'options-general.php',
                'menu_title' => sprintf(__('%s Options', 'wpseede'), $this->plugin_name),
                'page_title' => sprintf(__('%s Options', 'wpseede'), $this->plugin_name),
                'btn_title' => __('Update', 'wpseede')

            ],  $this->args['settings_config']);

            $this->settings = new Settings([
                'context_name' => $this->context_name
            ]);
        }
    }

    public function initScripts($args=[])
    {
        $this->scripts = new Scripts(wp_parse_args($args, [
            'context_name' => $this->context_name,
            'build_dir' => $this->base_dir . '/build',
            'build_dir_url' => $this->base_dir_url . '/build',
            'enqueue_build_index_front' => true,
            'enqueue_build_index_admin' => true,
        ]));
    }

    public function initTheme($args=[])
    {
        $this->parseArgs($args);
        add_action('after_setup_theme', [$this, '_initTheme']);
    }
    public function _initTheme()
    {
        $this->_addThemeSupport();
        $this->_addThemeImageSizes();
        $this->_addThemeNavMenus();
    }

    public function _addThemeSupport()
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

    public function _addThemeImageSizes()
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

    public function _addThemeNavMenus()
    {
        if($this->args['theme_menus'])
        {
            register_nav_menus($this->args['theme_menus']);
        }
    }

    public function initViewLoader()
    {
        $this->view_loader = new View_Loader([
            'context_name' => $this->context_name,
            'namespace' => $this->namespace,
            'base_dir' => $this->base_dir
        ]);
    }
}