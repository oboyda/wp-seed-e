jQuery.fn.extend({

    isWindowMobile: function(){
        return (this.width() < 992);
    },

    viewAddLoadedListener: function(eventName, cbk)
    {
        this.on(eventName, function(e, view){
            if(typeof cbk === 'function' && view.viewExists())
            {
                cbk(e, view);
            }
        });
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

            jQuery(document.body).triggerHandler("view_loaded", [_view, viewName]);

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

    viewUpdateParts: function(partsContent, triggerLoaded=false)
    {
        const parentView = this;
        Object.keys(partsContent).forEach((k) => {

            const part = parentView.find(".part-" + k);
            if(part.length)
            {
                part.html(partsContent[k]);
                if(triggerLoaded)
                {
                    part.viewTriggerLoaded(true);
                }
            }
            parentView.find(".part-" + k).html(partsContent[k]);
        });
    },

    viewExists: function()
    {
        return jQuery.contains(document.body, this.get(0));
    },

    viewAjaxLoad: function(loadAction="wpseede_load_view", viewName, args={}, cbk)
    {
        const parentView = this;

        let qArgs = {
            action: loadAction,
            view_name: viewName,
            view_args: (typeof args.viewArgs !== 'undefined') ? args.viewArgs : {},
            view_args_cast: (typeof args.viewArgsCast !== 'undefined') ? args.viewArgsCast : {},
            view_args_s: (typeof args.viewArgsS !== 'undefined') ? args.viewArgsS : ''
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