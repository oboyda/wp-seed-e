import { UtilsBase } from "./UtilsBase";

export class ViewUpdater 
{
    constructor(view, configElems={}, configsDefault={})
    {
        this.setView(view);
        this.setConfigElems(configElems);
        this.setConfigsDefault(configsDefault);

        // this.applyConfigs();
    }

    setView(view)
    {
        this.view = view;
    }

    setConfigElems(configElems)
    {
        if(!this.isset(this.elems))
        {
            this.elems = {};
        }

        Object.keys(configElems).forEach((name) => {
            const selector = configElems[name];
            this.elems[name] = this.view.find(selector);
        });
    }

    setConfigsDefault(configsDefault)
    {
        if(!this.isset(configsDefault.view))
        {
            configsDefault.view = {
                elemOn: true
            };
        }

        this.configsDefault = configsDefault;
        this.resetConfigs();

        this.setConfigsInit();
    }

    setConfigsInit()
    {
        const configsInit = this.view.data("configs");

        if(this.isset(configsInit))
        {
            Object.keys(configsInit).forEach((name) => {
                this.setConfig(name, configsInit[name], false);
            });
        }
    }

    setConfigs(configs, apply=false)
    {
        this.configs = configs;

        if(apply)
        {
            this.applyConfigs();
        }
    }

    setConfig(name, config, apply=false)
    {
        /*
        config = {
            text: "",
            html: "",
            addClass: "",
            removeClass: "",
            dataAtts: {},
            atts: {},
            events: {},
            elemOn: true
        }
        -------------------------
        */

        const _name = this.parseNamePathBase(name);
        
        if(_name.keyPath)
        {
            const _config = this.getObjectPath(_name.basePath, this.configs);

            if(this.isset(_config))
            {
                _config[_name.keyPath] = config;
            }
        }
        // else if(this.isset(this.configs[_name.basePath]))
        else if(typeof this.configs[_name.basePath] !== "undefined")
        {
            this.configs[_name.basePath] = config;
        }

        if(apply)
        {
            this.applyConfig(this.getNamePathElemName(name));
        }
    }

    resetConfigs(apply=false)
    {
        // this.setConfigs(Object.assign({}, this.configsDefault));
        this.setConfigs(JSON.parse(JSON.stringify(this.configsDefault)), apply);
    }

    getConfig(name)
    {
        const config = this.getObjectPath(name, this.configs);
        // const configDefault = this.getObjectPath(name, this.configsDefault);
        // return this.isset(config) ? config : (this.isset(configDefault) ? configDefault : null);

        return this.isset(config) ? config : null;
    }

    applyConfigs(names=null)
    {
        const _names = this.isset(names) ? names : Object.keys(this.configs);
        _names.forEach((name) => {
            this.applyConfig(name);
        });
    }

    applyConfig(name)
    {
        const _name = this.parseNamePathBase(name);
        const config = this.getConfig(name);
        const configElem = (_name.elemName == "view") ? this.view : ((typeof this.elems[_name.elemName] !== "undefined") ? this.elems[_name.elemName] : null);

        if(!(
            this.isset(configElem)
            // && this.isset(config) 
        )){
            return;
        }

        const _config = (_name.keyPath !== "") ? { [_name.keyPath]: config } : config;

        Object.keys(_config).forEach((k) => {

            const _c = _config[k];

            switch(k)
            {
                case "text":
                    configElem.text(_c);
                break;
                case "html":
                    if(typeof _c === "string"){
                        configElem.html(_c);
                    }else{
                        configElem.append(_c);
                    }
                break;
                case "class":
                    if(!(this.isset(_config.addClass) || this.isset(_config.removeClass)))
                    {
                        configElem.attr("class", _c);
                    }
                break;
                case "addClass":
                    configElem.addClass(_c);
                break;
                case "removeClass":
                    configElem.removeClass(_c);
                break;
                case "dataAtts":
                    Object.keys(_c).forEach((d) => {
                        configElem.attr(`data-${d}`, _c[d]);
                    });
                break;
                case "atts":
                    Object.keys(_c).forEach((a) => {
                        configElem.attr(a, _c[a]);
                    });
                break;
                case "events":
                    Object.keys(_c).forEach((e) => {
                        const callback = _c[e];
                        if(typeof callback === "function")
                        {
                            configElem.off(e, callback);
                            configElem.on(e, callback);
                        }
                    });
                break;
                case "elemOn":
                    if(_c)
                    {
                        configElem.removeClass("d-none");
                    }
                    else{
                        configElem.addClass("d-none");
                    }
                break;
            }
        });
    }

    /* ------------------------- */

    isset(val)
    {
        return UtilsBase.isset(val);
    }

    parseNamePath(name)
    {
        return UtilsBase.parseNamePath(name);
    }

    parseNamePathBase(name)
    {
        const _name = UtilsBase.parseNamePathBase(name);

        return {
            keyPath: _name.keyPath,
            basePath: _name.basePath,
            elemName: this.getNamePathElemName(_name.basePath)
        };
    }

    getNamePathElemName(name)
    {
        const _name = name.split(".");
        return (typeof _name[0] !== "undefined") ? _name[0] : name;
    }

    getObjectPath(path, obj, _obj=null)
    {
        return UtilsBase.getObjectPath(path, obj, _obj);
    }
}