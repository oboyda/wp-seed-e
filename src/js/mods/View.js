import { ViewUpdater } from "./ViewUpdater";

export class View extends ViewUpdater 
{
    constructor(view, configElems={}, configsDefault={})
    {
        super(view, configElems, configsDefault);
    }
}
