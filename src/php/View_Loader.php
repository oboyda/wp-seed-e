<?php 

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View_Loader extends \WPSEED\Action 
{
    protected $args;
    protected $context_name;
    protected $namespace;
    protected $base_dir;
    protected $views_args;

    public function __construct($args)
    {
        parent::__construct();

        $this->args = wp_parse_args($args, [
            'context_name' => 'wpseede',
            'namespace' => 'WPSEEDE',
            'base_dir' => __DIR__
        ]);
        $this->context_name = $this->args['context_name'];
        $this->namespace = $this->args['namespace'];
        $this->base_dir = $this->args['base_dir'];

        $this->views_args = [];

        add_action('wp_ajax_' . $this->context_name . '_load_view', [$this, 'loadView']);
        add_action('wp_ajax_nopriv_' . $this->context_name . '_load_view', [$this, 'loadView']);

        add_action('wp_head', [$this, 'printAjaxUrl']);

        add_action('init', [$this, 'initViewsArgs']);
        add_action('admin_head', [$this, 'cleanUpViewsArgs']);
        add_action('wp_head', [$this, 'cleanUpViewsArgs']);
        add_action('wp_footer', [$this, 'updateViewsArgs'], 1000);
    }

    public function loadView()
    {
        $view_name = $this->getReq('view_name');
        $view_args = $this->getReq('view_args', 'text', []);
        $view_args_cast = $this->getReq('view_args_cast', 'text', []);
        $view_args_s = $this->getReq('view_args_s', 'text', '');

        $_view_args = !empty($view_args) ? Utils_Base::castVals($view_args, $view_args_cast) : maybe_unserialize(stripslashes($view_args_s));

        $view_html = $this->getView($view_name, $_view_args);

        $this->setValue('view_name', $view_name);
        $this->setValue('view_html', $view_html);

        $this->respond();
    }

    protected function parseViewName($view_name)
    {
        $_view_name = [
            'view_name' => $view_name,
            'view_dir' => $this->base_dir . '/src/php/View/html',
            'view_namespace' => '\\' . $this->namespace . '\View'
        ];

        if(strpos($view_name, '/') !== false)
        {
            $view_mod_name = explode('/', $view_name);

            $_view_name['view_dir'] = $this->base_dir . '/mods/' . $view_mod_name[0] . '/View/html';
            $_view_name['view_namespace'] = '\\' . $this->namespace . '\\Mod\\' . $view_mod_name[0] . '\View';
            $_view_name['view_name'] = $view_mod_name[1];
        }

        return $_view_name;
    }

    public function getViewObject($view_name, $view_args=[])
    {
        $_view_name = $this->parseViewName($view_name);

        return wpseed_get_view_object($_view_name['view_name'], $view_args, $_view_name['view_dir'], $_view_name['view_namespace']);
    }

    public function getView($view_name, $view_args=[], $echo=false)
    {
        $_view_name = $this->parseViewName($view_name);

        return wpseed_get_view($_view_name['view_name'], $view_args, $echo, $_view_name['view_dir'], $_view_name['view_namespace']);
    }

    public function printView($view_name, $view_args=[])
    {
        $this->getView($view_name, $view_args, true);
    }

    public function renderViewAcf($block, $content, $is_preview, $post_id, $wp_block, $context)
    {
        $acf_prefix = 'acf/';

        $view = (strpos($block['name'], $acf_prefix) === 0) ? substr($block['name'], strlen($acf_prefix)) : $block['name'];
        $view_args = isset($block['view_args']) ? $block['view_args'] : [];
    
        // $view_args['block_id'] = isset($block['id']) ? $block['id'] : '';
        $view_args['block_id'] = Utils_Base::getBlockId($wp_block);
        $view_args['data'] = isset($block['data']) ? $block['data'] : '';
    
        $view_args['html_class'] = isset($block['className']) ? $block['className'] : '';
    
        $this->printView($view, $view_args);
    }

    public function printAjaxUrl()
    {
        $js_vars = apply_filters('wpseede_js_vars', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        ?>
        <script type="text/javascript">
            const wpseedeVars = <?php echo json_encode($js_vars); ?>
        </script>
        <?php
    }

    public function initViewsArgs()
    {
        if(wp_doing_ajax())
        {
            $this->view_args = get_option($this->context_name . '_views_args', []);
        }
    }

    public function cleanUpViewsArgs()
    {
        update_option($this->context_name . '_views_args', []);
    }

    public function updateViewsArgs()
    {
        update_option($this->context_name . '_views_args', $this->views_args);
    }

    public function saveViewArgs($view_id, $args)
    {
        $this->views_args[$view_id] = $args;
    }

    public function getViewArgs($view_id)
    {
        return isset($this->views_args[$view_id]) ? $this->views_args[$view_id] : [];
    }
}