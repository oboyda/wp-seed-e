jQuery.fn.extend({
    
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