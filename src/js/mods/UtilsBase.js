export class UtilsBase 
{
    static isMobile()
    {
        return (window.innerWidth < 992);
    }

    static isDesktop()
    {
        return !this.isMobile();
    }

    static isset(val)
    {
        return (typeof val !== "undefined" && val !== null);
    }

    static parseNamePath(name)
    {
        return (name.indexOf(".") > -1) ? name.split(".") : name;
    }

    static parseNamePathBase(name)
    {
        const _name = this.parseNamePath(name);
        if(Array.isArray(_name))
        {
            return {
                keyPath: _name[_name.length-1],
                basePath: _name.slice(0, _name.length-1).join("."),
            }
        }

        return {
            keyPath: "",
            basePath: _name
        }
    }

    static getObjectPath(path, obj, _obj=null)
    {
        if(!this.isset(_obj))
        {
            _obj = obj;
        }

        if(path === "")
        {
            return _obj;
        }

        if(Array.isArray(path))
        {
            path.forEach((_path) => {
                _obj = this.getObjectPath(_path, obj, _obj);
            });
        }
        else if(path.indexOf(".") > -1)
        {
            _obj = this.getObjectPath(path.split("."), obj, _obj);
        }
        else
        {
            _obj = (this.isset(_obj) && this.isset(_obj[path])) ? _obj[path] : null;
        }

        return _obj;
    }

    static arrayUnique(arr)
    {
        return arr.filter(function(value, index, _arr){
            return (_arr.indexOf(value) === index);
        });
    }
}
