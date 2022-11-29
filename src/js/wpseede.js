
const Wpseede = {

    init: function(args)
    {
        const _args = {
            contextName: 'wpseede',
            ajaxurl: wpseedeVars.ajaxurl,
            ...args
        }
        this.contextName = _args.contextName;
        this.ajaxurl = _args.ajaxurl;

        return Object.assign({}, this);
    },

    isMobile: function()
    {
        return (jQuery(window).width() < 992);
    }
};