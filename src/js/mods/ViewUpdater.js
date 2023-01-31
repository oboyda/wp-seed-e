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
            this.elems = {
                view: this.view
            };
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
            const configs = this.getObjectPath(_name.basePath, this.configs);

            if(this.isset(configs))
            {
                this._setConfig(_name.elemName, configs, _name.keyPath, config, apply);
            }
        }
        else if(typeof this.configs[_name.elemName] !== "undefined")
        {
            this.configs[_name.elemName] = config;

            this._setConfig(_name.elemName, this.configs[_name.elemName], null, apply);
        }
    }

    resetConfigs(apply=false)
    {
        this.setConfigs(JSON.parse(JSON.stringify(this.configsDefault)), apply);
    }

    getConfig(name, def=null)
    {
        const config = this.getObjectPath(name, this.configs);

        return this.isset(config) ? config : (this.isset(def) ? def : null);
    }

    applyConfigs()
    {
        Object.keys(this.configs).forEach((name) => {

            this.applyConfig(name, this.configs[name]);
        });
    }

    applyConfig(elemName, config, key=null)
    {
        const configElem = this._getConfigElem(elemName);
        if(!configElem)
        {
            return;
        }

        if(key === null)
        {
            Object.keys(config).forEach((_key) => {

                this.applyConfig(elemName, config[_key], _key);
            });
            return;
        }
        else{

            switch(key)
            {
                case "text":
                    configElem.text(config);
                break;
                case "html":
                    if(typeof config === "string"){
                        configElem.html(config);
                    }else{
                        configElem.append(config);
                    }
                break;
                case "class":
                    configElem.attr("class", config);
                break;
                case "addClass":
                    configElem.addClass(config);
                break;
                case "removeClass":
                    configElem.removeClass(config);
                break;
                case "dataAtts":
                    Object.keys(config).forEach((d) => {
                        configElem.attr(`data-${d}`, config[d]);
                    });
                break;
                case "atts":
                    Object.keys(config).forEach((a) => {
                        configElem.attr(a, config[a]);
                    });
                break;
                case "events":
                    Object.keys(config).forEach((e) => {
                        const callback = config[e];
                        if(typeof callback === "function")
                        {
                            configElem.off(e, callback);
                            configElem.on(e, callback);
                        }
                    });
                break;
            }
        }
    }

    /* ------------------------- */

    _setConfig(elemName, configs, key=null, value=null, apply=false)
    {
        if(key === null)
        {
            Object.keys(configs).forEach((k) => {
                
                this._setConfig(elemName, configs, k, configs[k], apply);
            });
            return;
        }

        if(key == "elemOn"){

            configs.elemOn = value;

            if(configs.elemOn)
            {
                this._setConfigRemoveClass(elemName, configs, "d-none", apply);
            }else{
                this._setConfigAddClass(elemName, configs, "d-none", apply);
            }
        }
        else if(key == "addClass"){

            this._setConfigAddClass(elemName, configs, value, apply);
        }
        else if(key == "removeClass"){

            this._setConfigRemoveClass(elemName, configs, value, apply);
        }
        else {
            configs[key] = value;

            if(apply)
            {
                this.applyConfig(elemName, configs[key], key);
            }
        }
    }

    _setConfigAddClass(elemName, elemConfigs, className, apply=false)
    {
        if(!this.isset(elemConfigs))
        {
            return;
        }

        if(!this.isset(elemConfigs.addClass))
        {
            elemConfigs.addClass = "";
        }

        if(
            elemConfigs.addClass.indexOf(className) < 0 
            // && (!this.isset(elemConfigs.removeClass) || (elemConfigs.removeClass.indexOf(className) < 0))
        ){
            elemConfigs.addClass += " " + className;
            elemConfigs.addClass = elemConfigs.addClass.trim();

            if(this.isset(elemConfigs.removeClass) && elemConfigs.removeClass.indexOf(className) > -1)
            {
                elemConfigs.removeClass = elemConfigs.removeClass.replace(className, "").trim();
            }

            if(apply)
            {
                this.applyConfig(elemName, elemConfigs.addClass, 'addClass');
            }
        }
    }
    _setConfigRemoveClass(elemName, elemConfigs, className, apply=false)
    {
        if(!this.isset(elemConfigs))
        {
            return;
        }

        if(!this.isset(elemConfigs.removeClass))
        {
            elemConfigs.removeClass = "";
        }

        if(
            elemConfigs.removeClass.indexOf(className) < 0 
            // && (!this.isset(elemConfigs.addClass) || (elemConfigs.addClass.indexOf(className) < 0))
        ){
            elemConfigs.removeClass += " " + className;
            elemConfigs.removeClass = elemConfigs.removeClass.trim();

            if(this.isset(elemConfigs.addClass) && elemConfigs.addClass.indexOf(className) > -1)
            {
                elemConfigs.addClass = elemConfigs.addClass.replace(className, "").trim();
            }

            if(apply)
            {
                this.applyConfig(elemName, elemConfigs.removeClass, 'removeClass');
            }
        }
    }

    _getConfigElem(name)
    {
        return this.isset(this.elems[name]) ? this.elems[name] : null;
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