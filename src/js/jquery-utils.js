jQuery.fn.extend({

    isWindowMobile: function(){
        return (this.width() < 992);
    },
    
    viewTriggerLoaded: function(triggerChildren=false)
    {
        this.each(function(){
            const _view = jQuery(this);
            const viewName = _view.data("view");
            if(typeof viewName !== 'undefined' && viewName)
            {
                jQuery(document.body).triggerHandler("view_loaded_" + viewName, [_view]);
            }
            if(triggerChildren)
            {
                _view.find(".view").viewTriggerLoaded();
            }
        });
    },

    viewReplace: function(html, triggerLoadedEvent=true, triggerChildren=false)
    {
        this.html(html);
        const _view = this.children();
        this.replaceWith(_view);
        if(triggerLoadedEvent)
        {
            _view.viewTriggerLoaded(triggerChildren);
        }
    },

    viewExists: function()
    {
        return jQuery.contains(document.body, this.get(0));
    },

    viewAjaxLoad: function(loadAction="wpseede_load_view", viewName, viewArgs={}, viewArgsCast={}, cbk)
    {
        const parentView = this;

        let qArgs = {
            action: loadAction,
            view_name: viewName,
            view_args: viewArgs,
            view_args_cast: viewArgsCast
        };

        jQuery.post(wpseedeVars.ajaxurl, qArgs, function(resp){

            if(resp.status && typeof resp.values.view_html !== 'undefined')
            {
                parentView.html(resp.values.view_html);
                parentView.viewTriggerLoaded(true);

                if(typeof cbk === 'function')
                {
                    cbk(resp);
                }
            }
        });
    }
});

// jQuery(function($)
// {
//     /*
//     Trigger views loaded event
//     ----------------------------------------
//     */
//     $(".view").viewTriggerLoaded();
// });