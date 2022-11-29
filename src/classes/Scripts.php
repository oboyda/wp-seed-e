<?php 

namespace WPSEEDE;

class Scripts 
{
    protected $args;

    protected $name;
    protected $prefix;

    protected $script_regs;
    protected $script_deps;

    protected $style_regs;
    protected $style_deps;

    public function __construct($args)
    {
        $this->args = wp_parse_args($args, [

            'context_name' => 'pboot',

            'build_dir' => __DIR__ . '/build',
            'build_dir_url' => '/build',
            'enqueue_build_index_front' => true,
            'enqueue_build_index_admin' => true,

            'script_regs' => [
                // 'script_name' => 'https://domain.com/script.js',
            ],
            'script_deps' => [
                // 'build_index_front' => ['jquery'],
                // 'build_index_admin' => ['jquery']
            ],
            'style_regs' => [
                // 'style_name' => 'https://domain.com/script.css',
            ],
            'style_deps' => [
                // 'build_index_front' => []
                // 'build_index_admin' => []
            ],

            'footer_scripts' => [],

            'version' => '1.0.0'
        ]);

        $this->context_name = $this->args['context_name'];
        $this->prefix = $this->context_name . '_';

        $this->script_regs = $this->args['script_regs'];
        $this->script_deps = $this->args['script_deps'];

        $this->style_regs = $this->args['style_regs'];
        $this->style_deps = $this->args['style_deps'];

        /*
        Register scripts
        ----------------------------------------
        */
        add_action('wp_enqueue_scripts', [$this, 'registerScripts']);
        add_action('admin_enqueue_scripts', [$this, 'registerScripts']);

        /*
        Register styles
        ----------------------------------------
        */
        add_action('wp_enqueue_scripts', [$this, 'registerStyles']);
        add_action('admin_enqueue_scripts', [$this, 'registerStyles']);

        /*
        Enqueue scripts on FRONT
        ----------------------------------------
        */
        add_action('wp_enqueue_scripts', [$this, 'enqueueScriptsFront']);

        /*
        Enqueue scripts on ADMIN
        ----------------------------------------
        */
        add_action('admin_enqueue_scripts', [$this, 'enqueueScriptsAdmin']);

        /*
        Enqueue styles on FRONT
        ----------------------------------------
        */
        add_action('wp_enqueue_scripts', [$this, 'enqueueStylesFront']);

        /*
        Enqueue styles on ADMIN
        ----------------------------------------
        */
        add_action('admin_enqueue_scripts', [$this, 'enqueueStylesAdmin']);
    }

    public function addScriptReg($reg)
    {
        $regs = is_array($reg) ? $regs : [$regs];
        $this->script_regs = array_merge($this->script_regs, $regs);
    }
    public function addScriptDep($dep)
    {
        $deps = is_array($deps) ? $deps : [$deps];
        $this->script_deps = array_merge($this->script_deps, $regs);
    }

    public function addStyleReg($reg)
    {
        $regs = is_array($reg) ? $regs : [$regs];
        $this->style_regs = array_merge($this->style_regs, $regs);
    }
    public function addStyleDep($dep)
    {
        $deps = is_array($deps) ? $deps : [$deps];
        $this->style_deps = array_merge($this->style_deps, $regs);
    }

    protected function getNameHandle($name, $reg_names=[])
    {
        return (!in_array($name, $reg_names) || strpos($name, $this->prefix) === 0) ? $name : $this->prefix . $name;
    }
    protected function getNameHandles($names, $reg_names)
    {
        if(!is_array($names))
        {
            $names = [$names];
        }

        foreach($names as $i => $name)
        {
            $names[$i] = $this->getNameHandle($name, $reg_names);
        }

        return $names;
    }

    protected function isFrontHandle($name)
    {
        $sk = '_front';
        return (substr($name, (0-strlen($sk))) === $sk);
    }
    protected function isAdminHandle($name)
    {
        $sk = '_admin';
        return (substr($name, (0-strlen($sk))) === $sk);
    }
    protected function isCommonHandle($name)
    {
        return (!$this->isFrontHandle($name) && !$this->isAdminHandle($name));
    }

    public function registerScripts()
    {
        if(
            $this->args['enqueue_build_index_front'] || 
            $this->args['enqueue_build_index_admin']
        ){
            $this->script_regs = array_merge([
                'build_index_front' => '',
                'build_index_admin' => '',
            ], $this->script_regs); 

            $this->script_deps = array_merge_recursive([
                'build_index_front' => ['jquery'],
                'build_index_admin' => ['jquery']
            ], $this->script_deps);
        }

        $script_regs_names = array_keys($this->script_regs);
        foreach($this->script_regs as $name => $script_reg)
        {
            $deps = isset($this->script_deps[$name]) ? $this->getNameHandles($this->script_deps[$name], $script_regs_names) : [];
            $name_handle = $this->getNameHandle($name, $script_regs_names);

            switch($name)
            {
                case 'build_index_front':

                    $asset_file = $this->args['build_dir'] . '/front.asset.php';
                    if(file_exists($asset_file))
                    {
                        $asset = include($asset_file);
            
                        wp_register_script(
                            $name_handle,
                            $this->args['build_dir_url'] . '/front.js',
                            array_merge($asset['dependencies'], $deps),
                            $asset['version'],
                            in_array($name, $this->args['footer_scripts'])
                        );
                    }
                break;

                case 'build_index_admin':

                    $asset_file = $this->args['build_dir'] . '/admin.asset.php';
                    if(file_exists($asset_file))
                    {
                        $asset = include($asset_file);
            
                        wp_register_script(
                            $name_handle,
                            $this->args['build_dir_url'] . '/admin.js',
                            array_merge($asset['dependencies'], $deps),
                            $asset['version'],
                            in_array($name, $this->args['footer_scripts'])
                        );
                    }
                break;

                default:

                    if(strpos($script_reg, 'http') === 0)
                    {
                        wp_register_script(
                            $name_handle,
                            $script_reg,
                            $deps,
                            $this->args['version'],
                            in_array($name, $this->args['footer_scripts'])
                        );
                    }
            }
        }
    }

    public function registerStyles()
    {
        if(
            $this->args['enqueue_build_index_front'] || 
            $this->args['enqueue_build_index_admin']
        ){
            $this->style_regs = array_merge([
                'build_index_front' => '',
                'build_index_admin' => '',
                // 'fonts' => $this->args['assets_dir_url'] . '/fonts/fonts.css',
            ], $this->style_regs); 

            // $this->style_deps = array_merge_recursive([
            //     'build_index_front' => [],
            //     'build_index_admin' => []
            // ], $this->style_deps);
        }

        $style_regs_names = array_keys($this->style_regs);
        foreach($this->style_regs as $name => $style_reg)
        {
            $deps = isset($this->style_deps[$name]) ? $this->getNameHandles($this->style_deps[$name], $style_regs_names) : [];
            $name_handle = $this->getNameHandle($name, $style_regs_names);

            switch($name)
            {
                case 'build_index_front':

                    $asset_file = $this->args['build_dir'] . '/front.asset.php';
                    if(file_exists($asset_file))
                    {
                        $asset = include($asset_file);

                        wp_register_style(
                            $name_handle,
                            $this->args['build_dir_url'] . '/front.css',
                            array_merge($asset['dependencies'], $deps),
                            $asset['version']
                        );
                    }
                break;

                case 'build_index_admin':

                    $asset_file = $this->args['build_dir'] . '/admin.asset.php';
                    if(file_exists($asset_file))
                    {
                        $asset = include($asset_file);

                        wp_register_style(
                            $name_handle,
                            $this->args['build_dir_url'] . '/admin.css',
                            array_merge($asset['dependencies'], $deps),
                            $asset['version']
                        );
                    }
                break;

                default:

                    if(strpos($style_reg, 'http') === 0)
                    {
                        wp_register_style(
                            $name_handle,
                            $style_reg,
                            $deps,
                            $this->args['version']
                        );
                    }
            }
        }
    }

    public function enqueueScriptsFront()
    {
        $script_regs_names = array_keys($this->script_regs);
        foreach($script_regs_names as $name)
        {
            if($this->isFrontHandle($name) || $this->isCommonHandle($name))
            {
                $name_handle = $this->getNameHandle($name, $script_regs_names);
                wp_enqueue_script($name_handle);
            }
        }

        if(isset($this->script_regs['build_index_front']))
        {
            wp_localize_script($this->prefix . 'build_index_front', $this->context_name . 'IndexVars', apply_filters($this->context_name . '_js_index_vars', [
                'ajaxurl' => admin_url('admin-ajax.php')
            ]));
        }
    }

    public function enqueueScriptsAdmin()
    {
        $script_regs_names = array_keys($this->script_regs);
        foreach(array_keys($this->script_regs) as $name)
        {
            if($this->isAdminHandle($name) || $this->isCommonHandle($name))
            {
                $name_handle = $this->getNameHandle($name, $script_regs_names);
                wp_enqueue_script($name_handle);
            }
        }

        if(isset($this->script_regs['build_index_admin']))
        {
            wp_localize_script($this->prefix . 'build_index_admin', $this->context_name . 'IndexVars', apply_filters($this->context_name . '_js_index_vars', [
                'ajaxurl' => admin_url('admin-ajax.php')
            ]));
        }
    }

    public function enqueueStylesFront()
    {
        $style_regs_names = array_keys($this->style_regs);
        foreach($style_regs_names as $name)
        {
            if($this->isFrontHandle($name) || $this->isCommonHandle($name))
            {
                $name_handle = $this->getNameHandle($name, $style_regs_names);
                wp_enqueue_style($name_handle);
            }
        }
    }

    public function enqueueStylesAdmin()
    {
        $style_regs_names = array_keys($this->style_regs);
        foreach($style_regs_names as $name)
        {
            if($this->isAdminHandle($name) || $this->isCommonHandle($name))
            {
                $name_handle = $this->getNameHandle($name, $style_regs_names);
                wp_enqueue_style($name_handle);
            }
        }
    }
}