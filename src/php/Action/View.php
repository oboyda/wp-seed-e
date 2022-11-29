<?php 

namespace WPSEEDE\Action;

class View extends \WPSEED\Action 
{
    protected $args;

    protected $context_name;

    public function __construct()
    {
        parent::__construct();

        $this->args = [
            'context_name' => 'wpseede'
        ];
        $this->context_name = $this->args['context_name'];

        add_action('wp_ajax_' . $this->context_name . '_load_view', [$this, 'loadView']);
        add_action('wp_ajax_nopriv_' . $this->context_name . '_load_view', [$this, 'loadView']);
    }

    public function loadView()
    {
        $view_name = $this->getReq('view_name');
        $view_args = $this->getReq('view_args', 'text', []);
        $view_args_cast = $this->getReq('view_args_cast', 'text', []);

        if($view_name)
        {
            $this->setValue('view_name', $view_name);
            $this->setValue('view_html', ofrp_get_view($view_name, Utils_Base::castVals($view_args, $view_args_cast)));
        }

        $this->respond();
    }
}