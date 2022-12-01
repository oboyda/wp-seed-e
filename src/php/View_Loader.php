<?php 

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View_Loader extends \WPSEED\Action 
{
    protected $args;
    protected $context_name;
    protected $namespace;
    protected $base_dir;

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

        add_action('wp_ajax_' . $this->context_name . '_load_view', [$this, 'loadView']);
        add_action('wp_ajax_nopriv_' . $this->context_name . '_load_view', [$this, 'loadView']);

        add_action('wp_head', [$this, 'printAjaxUrl']);
    }

    public function loadView()
    {
        $view_name = $this->getReq('view_name');
        $view_args = $this->getReq('view_args', 'text', []);
        $view_args_cast = $this->getReq('view_args_cast', 'text', []);
        $view_html = $this->getView($view_name, Utils_Base::castVals($view_args, $view_args_cast));

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
}