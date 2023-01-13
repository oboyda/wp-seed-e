class Wpseede_View_Registry 
{
    constructor()
    {
        this._setRegistry();
    }

    _setRegistry()
    {
        if(!this.isset(window.viewRegistry))
        {
            window.viewRegistry = {};
        }

        this.registry = window.viewRegistry;
    }

    getViewRegistry(viewName, viewId=null)
    {
        const viewRegistry = this.isset(this.registry[viewName]) ? this.registry[viewName] : null;

        if(!viewId)
        {
            return viewRegistry;
        }

        return this.isset(viewRegistry[viewId]) ? viewRegistry[viewId] : null;
    }

    addViewRegistry(view)
    {
        if(!view.length)
        {
            return null;
        }

        const viewName = view.data("view");
        const viewId = this.getViewId(view, true);

        if(!(this.isset(viewName) && this.isset(viewId)))
        {
            return null;
        }

        if(!this.isset(this.registry[viewName]))
        {
            this.registry[viewName] = {};
        }

        this.registry[viewName][viewId] = {
            id: viewId,
            // loadedCallback: loadedCallback,
            interface: null
        };

        return this.registry[viewName][viewId];
    }

    removeViewRegistry(view)
    {
        if(!view.length)
        {
            return false;
        }

        if(view.length > 1)
        {
            const _this = this;
            view.each(() => {
                _this.removeViewRegistry(jQuery(this).find(".view"));
            });
        }

        const viewName = view.data("view");
        const viewId = this.getViewId(view, false);

        if(!(this.isset(viewName) && this.isset(viewId)))
        {
            return false;
        }

        if(this.isset(this.registry[viewName]) && this.isset(this.registry[viewName][viewId]))
        {
            delete this.registry[viewName][viewId];

            if(!Object.keys(this.registry[viewName]).length)
            {
                delete this.registry[viewName];
            }

            return true;
        }
    }

    getViewInterfaces(viewName, viewId=null)
    {
        const viewRegistry = this.getViewRegistry(viewName, viewId);

        if(!viewId)
        {
            let viewInterfaces = [];

            if(viewRegistry !== null)
            {
                Object.keys(viewRegistry).forEach((viewId) => {
                    if(viewRegistry[viewId].interface !== null)
                    {
                        viewInterfaces.push(viewRegistry[viewId].interface);
                    }
                });
            }
            return viewInterfaces;
        }

        return (viewRegistry.interface !== null) ? viewRegistry.interface : null;
    }

    getViewInterfaceSingle(viewName, viewId=null)
    {
        const viewInterfaces = this.getViewInterfaces(viewName, viewId);
        return Array.isArray(viewInterfaces) ? (this.isset(viewInterfaces[0]) ? viewInterfaces[0] : null) : viewInterfaces;
    }

    // addViewInterface(viewName, viewId, iObj)
    // {
    //     let viewRegistry = this.getViewRegistry(viewName, viewId);

    //     if(viewRegistry !== null)
    //     {
    //         viewRegistry.interface = iObj;
    //     }
    // }

    // removeViewInterface(viewName, viewId)
    // {
    //     let viewRegistry = this.getViewRegistry(viewName, viewId);

    //     if(viewRegistry !== null)
    //     {
    //         viewRegistry.interface = null;
    //     }
    // }

    getViewId(view, genId=false)
    {
        const viewId = view.attr("id");

        if((!this.isset(viewId) || viewId === "") && genId)
        {
            const _viewId = this.genId();
            view.attr("id", _viewId);

            return _viewId;
        }

        return viewId;
    }

    genId(length=16)
    {
        let id = "";
        const chars = "abcdefghijklmnopkrstuvwxyz0123456789";
        for(let i = 0; i < length; i++)
        {
            id += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return "view-"+id+"-js";
    }

    isset(val)
    {
        return (typeof val !== "undefined" && val !== null);
    }
}

/* ------------------------- */

jQuery.fn.extend({

    viewAddLoadedListener: function(eventName, loadedCallback=null)
    {
        this.on(eventName, function(e, view, viewRegistry){

            if(viewRegistry !== null && typeof loadedCallback === "function")
            {
                loadedCallback(e, view, viewRegistry);
            }
        });
    },
    
    viewTriggerLoaded: function(triggerChildren=false)
    {
        const _Wpseede_View_Registry = new Wpseede_View_Registry();

        this.each(function(){

            const _view = jQuery(this);
            const viewName = _view.data("view");

            const viewRegistry = _Wpseede_View_Registry.addViewRegistry(_view);

            if(viewRegistry !== null)
            {
                jQuery(document.body).triggerHandler("view_loaded_" + viewName, [_view, viewRegistry]);
                jQuery(document.body).triggerHandler("view_loaded", [_view, viewName, viewRegistry]);

                if(triggerChildren)
                {
                    _view.find(".view").viewTriggerLoaded(false);
                }
            }
        });
    },

    viewReplace: function(html, triggerLoadedEvent=true, triggerChildren=true)
    {
        const _Wpseede_View_Registry = new Wpseede_View_Registry();
        _Wpseede_View_Registry.removeViewRegistry(this);

        this.after(html);
        this.remove();

        if(triggerLoadedEvent)
        {
            _view.viewTriggerLoaded(triggerChildren);
        }
    },

    viewUpdateParts: function(partsContent, triggerLoaded=true)
    {
        const parentView = this;

        const _Wpseede_View_Registry = new Wpseede_View_Registry();

        Object.keys(partsContent).forEach((k) => {

            const part = parentView.find(".part-" + k);
            if(part.length)
            {
                _Wpseede_View_Registry.removeViewRegistry(part);

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

        const _Wpseede_View_Registry = new Wpseede_View_Registry();

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
                _Wpseede_View_Registry.removeViewRegistry(parentView.find(".view"));

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
