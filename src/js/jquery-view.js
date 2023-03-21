
if(typeof WpseedeViewRegistry === "undefined")
{
    class WpseedeViewRegistry 
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
            viewName = this.sanitizeViewName(viewName);
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

            const _viewName = view.data("view");
            const viewName = this.sanitizeViewName(_viewName);
            const viewId = this.getViewId(view, true);

            if(!(this.isset(viewName) && this.isset(viewId)))
            {
                return null;
            }

            if(!this.isset(this.registry[viewName]))
            {
                this.registry[viewName] = {};
            }

            if(typeof this.registry[viewName][viewId] !== "undefined")
            {
                return this.registry[viewName][viewId];
            }

            this.registry[viewName][viewId] = {
                name: viewName,
                id: viewId,
                interface: null,
                registry: this
            };
            this.registry[viewName][viewId].addInterface = (viewInterface) => {

                this.registry[viewName][viewId].interface = viewInterface;
                jQuery(document.body).triggerHandler("wpseede_interface_ready_" + _viewName, [viewInterface, _viewName, viewId]);
            };

            return this.registry[viewName][viewId];
        }

        removeViewRegistry(view, removeChildren=true)
        {
            if(!view.length)
            {
                return;
            }

            if(view.length > 1)
            {
                const _this = this;
                view.each(() => {
                    if(removeChildren){
                        _this.removeViewRegistry(jQuery(this).find(".view"), false);
                    }else{
                        _this.removeViewRegistry(jQuery(this));
                    }
                });
                return;
            }

            const viewName = this.sanitizeViewName(view.data("view"));
            const viewId = this.getViewId(view, false);

            if(!(this.isset(viewName) && this.isset(viewId)))
            {
                return;
            }

            if(this.isset(this.registry[viewName]) && this.isset(this.registry[viewName][viewId]))
            {
                delete this.registry[viewName][viewId];

                if(!Object.keys(this.registry[viewName]).length)
                {
                    delete this.registry[viewName];
                }
            }
        }

        getViewInterfaces(viewName, viewId=null)
        {
            viewName = this.sanitizeViewName(viewName);

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
            viewName = this.sanitizeViewName(viewName);

            const viewInterfaces = this.getViewInterfaces(viewName, viewId);
            return Array.isArray(viewInterfaces) ? (this.isset(viewInterfaces[0]) ? viewInterfaces[0] : null) : viewInterfaces;
        }

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

        sanitizeViewName(viewName)
        {
            return (typeof viewName === "string") ? viewName.replace(/\./g, "--") : null;
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
}

/* ------------------------- */

jQuery.fn.extend({

    viewAddLoadedListener: function(eventName, loadedCallback=null)
    {
        if(eventName.indexOf("view_loaded") !== 0)
        {
            eventName = "view_loaded_" + eventName;
        }

        this.on(eventName, function(e, view, viewRegistry){

            if(viewRegistry !== null && typeof loadedCallback === "function")
            {
                loadedCallback(e, view, viewRegistry);
            }
        });
    },
    
    viewTriggerLoaded: function(triggerChildren=false)
    {
        const _WpseedeViewRegistry = new WpseedeViewRegistry();

        this.each(function(){

            const _view = jQuery(this);
            const viewName = _view.data("view");

            if(typeof viewName === "undefined")
            {
                return;
            }

            const viewRegistry = _WpseedeViewRegistry.addViewRegistry(_view);

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

    viewRemoveRegistry: function()
    {
        const _WpseedeViewRegistry = new WpseedeViewRegistry();
        _WpseedeViewRegistry.removeViewRegistry(this);
    },

    viewReplace: function(html, triggerLoadedEvent=true, triggerChildren=true)
    {
        this.viewRemoveRegistry();

        this.html(html);
        this.replaceWith(this.children());

        if(triggerLoadedEvent)
        {
            this.viewTriggerLoaded(triggerChildren);
        }
    },

    viewInsert: function(html, triggerLoadedEvent=true)
    {
        this.find(".view").viewRemoveRegistry();

        this.html(html);

        if(triggerLoadedEvent)
        {
            this.find(".view").viewTriggerLoaded();
        }
    },

    viewAppend: function(html, triggerLoadedEvent=true)
    {
        this.append(html);

        if(triggerLoadedEvent)
        {
            this.find(".view").viewTriggerLoaded();
        }
    },

    viewRemove: function(view)
    {
        this.viewRemoveRegistry();

        this.remove();
    },

    viewUpdateParts: function(partsContent, triggerLoaded=true)
    {
        const parentView = this;

        Object.keys(partsContent).forEach((k) => {

            const part = parentView.find(".part-" + k);
            if(part.length)
            {
                part.viewInsert(partsContent[k]);
            }
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
            view_args: (typeof args.viewArgs !== "undefined") ? args.viewArgs : {},
            view_args_cast: (typeof args.viewArgsCast !== "undefined") ? args.viewArgsCast : {},
            view_args_s: (typeof args.viewArgsS !== "undefined") ? args.viewArgsS : '',
            view_id: this.attr("id"),
            view_block_id: this.data("block_id")
        };

        jQuery.post(wpseedeVars.ajaxurl, qArgs, function(resp){

            if(resp.status && typeof resp.values.view_html !== "undefined")
            {
                parentView.viewInsert(resp.values.view_html, true, true);

                if(typeof cbk === 'function')
                {
                    cbk(resp);
                }
            }
        });
    },

    viewAjaxLoadParts: function(loadAction="wpseede_load_view_parts", viewName, args={}, cbk)
    {
        const _this = this;

        let qArgs = {
            action: loadAction,
            view_name: viewName,
            view_args: (typeof args.viewArgs !== "undefined") ? args.viewArgs : {},
            view_args_cast: (typeof args.viewArgsCast !== "undefined") ? args.viewArgsCast : {},
            view_args_s: (typeof args.viewArgsS !== "undefined") ? args.viewArgsS : '',
            view_id: this.attr("id")
        };

        jQuery.post(wpseedeVars.ajaxurl, qArgs, function(resp){

            if(resp.status && typeof resp.values.view_html !== "undefined")
            {
                _this.viewUpdateParts(resp.values.view_html, true);

                if(typeof cbk === 'function')
                {
                    cbk(resp);
                }
            }
        });
    }
});
