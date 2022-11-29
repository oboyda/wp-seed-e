<?php 

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View_Loader extends \WPSEED\Action 
{
    protected $args;
    protected $context_name;

    public function __construct($args)
    {
        parent::__construct();

        $this->args = wp_parse_args($args, [
            'context_name' => 'wpseede'
        ]);
        $this->context_name = $this->args['context_name'];

        add_action('wp_ajax_' . $this->context_name . '_load_view', [$this, 'loadView']);
        add_action('wp_ajax_nopriv_' . $this->context_name . '_load_view', [$this, 'loadView']);

        add_action('wp_head', [$this, 'printAjaxUrl']);
    }

    public function loadView()
    {
        $view_name = $this->getReq('view_name');
        $view_args = $this->getReq('view_args', 'text', []);
        $view_args_cast = $this->getReq('view_args_cast', 'text', []);

        if($view_name)
        {
            $view_html = '';

            if(strpos($view_name, '/') !== false)
            {
                $view_mod_name = explode('/', $view_name);
                $view_func = $this->context_name . '_get_mod_view';
                if(function_exists($view_func))
                {
                    $view_html = $view_func($view_mod_name[0], $view_mod_name[1], $view_args_cast);
                }
            }
            else{
                $view_func = $this->context_name . '_get_view';
                if(function_exists($view_func))
                {
                    $view_html = $view_func($view_name, $view_args_cast);
                }
            }

            $this->setValue('view_name', $view_name);
            $this->setValue('view_html', $view_html);
        }

        $this->respond();
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