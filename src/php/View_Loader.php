<?php 

namespace WPSEEDE;

use WPSEEDE\Utils\Base as Utils_Base;

class View_Loader extends \WPSEED\Action 
{
    protected $args;
    var $context_name;
    var $namespace;
    var $base_dir;
    var $views_args;

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

        add_filter($this->context_name . '_load_view_args', [$this, 'filterLoadViewArgsAcf'], 10, 2);

        add_action('wp_ajax_' . $this->context_name . '_load_view', [$this, 'loadView']);
        add_action('wp_ajax_nopriv_' . $this->context_name . '_load_view', [$this, 'loadView']);
        add_action('wp_ajax_' . $this->context_name . '_load_view_parts', [$this, 'loadView']);
        add_action('wp_ajax_nopriv_' . $this->context_name . '_load_view_parts', [$this, 'loadView']);

        add_action('wp_head', [$this, 'printJsVars']);

        // add_action('wp_footer', [$this, 'printViewsArgs'], 1000);
        // add_action('admin_footer', [$this, 'printViewsArgs'], 1000);
    }

    public function loadView()
    {
        $view_name = $this->getReq('view_name');
        $view_id = $this->getReq('view_id');
        $block_id = $this->getReq('block_id');
        $view_args = $this->getReq('view_args', 'text', []);
        $view_args_cast = $this->getReq('view_args_cast', 'text', []);
        $view_args_s = $this->getReq('view_args_s', 'text', '');

        $view_args = wp_parse_args($view_args, [
            'id' => $view_id,
            'block_id' => $block_id
        ]);

        $_view_args = !empty($view_args) ? Utils_Base::castVals($view_args, $view_args_cast) : maybe_unserialize(stripslashes($view_args_s));

        $view_args = apply_filters($this->context_name . '_load_view_args', $_view_args, $view_name);

        $this->setValue('view_name', $view_name);

        if(strpos($this->getReq('action'), $this->context_name . '_load_view_parts') !== false)
        {
            $view_obj = $this->getViewObject($view_name, $_view_args);
            if(isset($view_obj) && method_exists($view_obj, 'getChildParts'))
            {
                $this->setValue('view_html', $view_obj->getChildParts());
            }else{
                $this->setValue('view_html', []);
            }
        }else{
            $this->setValue('view_html', $this->getView($view_name, $_view_args));            
        }

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

        $view_args = apply_filters($this->context_name . '_get_view_args', $view_args, $view_name);

        return wpseed_get_view($_view_name['view_name'], $view_args, $echo, $_view_name['view_dir'], $_view_name['view_namespace']);
    }

    public function printView($view_name, $view_args=[])
    {
        $this->getView($view_name, $view_args, true);
    }

    public function renderViewAcf($block, $content, $is_preview, $post_id, $wp_block, $context)
    {
        $acf_prefix = 'acf/';

        $view_name = (strpos($block['name'], $acf_prefix) === 0) ? substr($block['name'], strlen($acf_prefix)) : $block['name'];

        $view_args = isset($block['data']) ? $block['data'] : [];

        // $view_args['id'] = 'acf-' . Utils_Base::getBlockId($wp_block);
        $view_args['block_id'] = 'acf-' . Utils_Base::getBlockId($wp_block);
        // $view_args['html_class'] = isset($block['className']) ? $block['className'] : '';

        $view_args = $this->filterLoadViewArgsAcf($view_args, $view_name, false);

        $this->printView($view_name, $view_args);
    }

    public function filterLoadViewArgsAcf($view_args, $view_name, $load_block_args=true)
    {
        $block_id = !empty($view_args['block_id']) ? $view_args['block_id'] : (isset($view_args['id']) ? $view_args['id'] : '');

        //Strip context name and view name from field names
        if(strpos($block_id, 'acf-') === 0)
        {
            if($load_block_args)
            {
                $_block_id = substr($block_id, strlen('acf-'), strlen($block_id));
                if($_block_id)
                {
                    $block_data = Utils_Base::getPostBlockData($_block_id, null);
                    if(is_array($block_data))
                    {
                        $view_args = array_merge($view_args, $block_data);
                    }
                }
            }
            
            $view_args = $this->stripArgsPrefixesAcf($view_args);
        }

        return $view_args;
    }
    
    protected function stripArgsPrefixesAcf($view_args, $debug=false)
    {
        // Convert ACF field ids to meta names
        foreach($view_args as $key => $value)
        {
            $acf_id = function_exists('acf_is_field_key') ? (acf_is_field_key($key) ? $key : ( (is_string($value) && acf_is_field_key($value)) ? $value : '') ) : '';
            if($acf_id)
            {
                $field_obj = get_field_object($acf_id);

                if(isset($field_obj['name']) && isset($field_obj['value']))
                {
                    $view_args[$field_obj['name']] = $field_obj['value'];
                    unset($view_args[$key]);
                }
            }
        }
        
        $view_args = Utils_Base::stripBlockDataFieldsPrefixes($view_args, $this->context_name);

        return $view_args;
    }

    public function printJsVars()
    {
        $js_vars = apply_filters('wpseede_js_vars', [
            'ajaxurl' => admin_url('admin-ajax.php')
        ]);
        ?>
        <script type="text/javascript">
            if(typeof wpseedeVars === "undefined"){
                var wpseedeVars = <?php echo json_encode($js_vars); ?>;
            }
        </script>
        <?php
    }

    // public function saveViewArgs($view_id, $args)
    // {
    //     $this->views_args[$view_id] = $args;
    // }

    // public function printViewsArgs()
    // {
    //     echo '<script type="text/javascript">const ' . $this->context_name . 'ViewsArgs = ' . json_encode($this->views_args) . ';</script>';
    // }

    // public function getViewArgs($view_id)
    // {
    //     return isset($this->views_args[$view_id]) ? $this->views_args[$view_id] : [];
    // }
}