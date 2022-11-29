
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
    },

    showFormStatus: function(form, resp)
    {
        if(typeof resp.error_fields !== "undefined")
        {
            resp.error_fields.map((errorField) => {
                const errorInput = form.find("[name='"+errorField+"']");
                errorInput.addClass("error");
                errorInput.on("change", function(){
                    jQuery(this).removeClass("error");
                });
            });
        }
        const messagesCont = form.find(".messages-cont");
        if(typeof resp.messages !== "undefined" && messagesCont.length)
        {
            messagesCont.html(resp.messages);
        }
    },

    loadView: function(parentView, viewName, viewArgs={}, viewArgsCast={}, cbk)
    {
        let qArgs = {
            action: this.contextName+"_load_view",
            view_name: viewName,
            view_args: viewArgs,
            view_args_cast: viewArgsCast
        };

        jQuery.post(this.ajaxurl, qArgs, function(resp){

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
    },

    initAjaxForms: function(ajaxForms)
    {
        const _this = this;
        
        ajaxForms.each(function(){

            const form = jQuery(this);
    
            form.on("submit", function(e){
                e.preventDefault();
    
                const btnSubmit = form.find("button[type='submit']");
                btnSubmit.prop("disabled", true);
    
                const data = new FormData(form.get(0));
    
                form.triggerHandler(_this.contextName + "_submit_ajax_form_std_before", [data]);
    
                $.ajax({
                    url: form.attr("action") ? form.attr("action") : _this.ajaxurl,
                    type: "POST",
                    enctype: form.attr("enctype") ? form.attr("enctype") : "application/x-www-form-urlencoded",
                    data: data,
                    processData: false,
                    contentType: false,
                    cache: false,
                    // timeout: 800000
                })
                .done(function(resp){
                    if(resp.status)
                    {
                        if(resp.redirect)
                        {
                            location.assign(resp.redirect);
                        }
                        else if(resp.reload)
                        {
                            location.reload();
                        }
        
                        form.get(0).reset();
                    }
    
                    btnSubmit.prop("disabled", false);
    
                    _Wpseede.showFormStatus(form, resp);
    
                    form.triggerHandler(_this.contextName + "_submit_ajax_form_std_success", [resp, data]);
                })
                .fail(function(error)
                {
                    console.log("ERROR : ", error);
                })
                .always(function(resp)
                {
                    form.triggerHandler(_this.contextName + "_submit_ajax_form_std_after", [resp, data]);
                })
            });
    
            form.find(".change-submit").on("change", function(){
                form.submit();
            });
    
            form.on(_this.contextName + "_show_form_status", function(e, resp){
                _Wpseede.showFormStatus(form, resp);
            });
    
            /*
            .view.form-files-drop
            -------------------------
            */
    
            const getFileSummary = function(files){
                let summ = [];
                const filesArr = Array.isArray(files) ? files : Array.from(files);
                filesArr.forEach((file) => {
                    if(typeof file.name !== 'undefined')
                    {
                        summ.push(file.name);
                    }
                });
                return summ.join(', ');
            }
    
            form.find(".view.form-files-drop").each(function(){
    
                const filesDropView = jQuery(this);
    
                const dropArea = filesDropView.find(".drop-area");
                const dropSummary = filesDropView.find(".drop-summary");
                const fileInput = filesDropView.find("input[type='file']");
                const fileInputElem = fileInput.get(0);
    
                if(dropArea.length && fileInput.length)
                {
                    dropArea.on("dragenter", function(e){
                        dropArea.addClass("file-over");
                    });
                    dropArea.on("dragleave", function(e){
                        dropArea.removeClass("file-over");
                    });
                    dropArea.on("dragover", function(e){
                        e.preventDefault();
                    });
                    dropArea.on("drop", function(e){
                        e.preventDefault();
                        const _e = e.originalEvent;
    
                        const filesArr = Array.from(_e.dataTransfer.files);
    
                        // Attach files to the input
                        if((fileInputElem.multiple && filesArr.length > 0) || (!fileInputElem.multiple && filesArr.length === 1))
                        {
                            fileInputElem.files = _e.dataTransfer.files;
                            fileInput.triggerHandler("change");
                        }
                    });
    
                    fileInput.on("change", function(){
                        if(fileInputElem.files.length){
                            filesDropView.addClass("has-files");
                        }else{
                            filesDropView.removeClass("has-files");
                        }
                        dropSummary.html(getFileSummary(fileInputElem.files));
                    });
                }
            });
    
            /*
            .view.form-input-dates
            -------------------------
            */
    
            form.find(".view.form-input-dates").each(function(){
    
                const datesRangeView = jQuery(this);
    
                const dateFromFieldDisplay = datesRangeView.find(".date-from input.date-from-display");
                const dateFromFieldAlt = datesRangeView.find(".date-from input.date-from");
                const datepickerFromElem = datesRangeView.find(".date-from .datepicker");
    
                const dateTillFieldDisplay = datesRangeView.find(".date-till input.date-till-display");
                const dateTillFieldAlt = datesRangeView.find(".date-till input.date-till");
                const datepickerTillElem = datesRangeView.find(".date-till .datepicker");
    
                if(
                    datepickerFromElem.length && 
                    !datepickerFromElem.hasClass("hasDatepicker") && 
                    // dateFromFieldDisplay.length && 
                    // !dateFromFieldDisplay.hasClass("hasDatepicker") && 
                    typeof $.fn.datepicker !== "undefined"
                ){
                    datepickerFromElem.datepicker({
                    // dateFromFieldDisplay.datepicker({
                        dateFormat: "dd/mm/yy",
                        altField: dateFromFieldAlt,
                        altFormat: "yy-mm-dd",
                        minDate: new Date(),
                        // defaultDate: dateFromFieldDisplay.val() ? dateFromFieldDisplay.val() : null,
                        onSelect: function(dateText, datePicker){
                            dateFromFieldDisplay.val(dateText);
                            dateFromFieldAlt.change();
                        }
                    });
                    if(datepickerTillElem.length)
                    // if(dateTillFieldDisplay.length)
                    {
                        dateFromFieldAlt.on("change", function(){
                            const minDate = new Date(this.value);
                            datepickerTillElem.datepicker("option", "minDate", minDate);
                            // dateTillFieldDisplay.datepicker("option", "minDate", minDate);
                        });
                    }
                }
                dateFromFieldDisplay.on("focus", function(){
                    datepickerFromElem.removeClass("d-none");
                });
                dateFromFieldDisplay.on("blur", function(){
                    setTimeout(function(){
                        datepickerFromElem.addClass("d-none");
                    }, 1000);
                });
    
                if(
                    datepickerTillElem.length && 
                    !datepickerTillElem.hasClass("hasDatepicker") && 
                    // dateTillFieldDisplay.length && 
                    // !dateTillFieldDisplay.hasClass("hasDatepicker") && 
                    typeof $.fn.datepicker !== "undefined"
                ){
                    datepickerTillElem.datepicker({
                    // dateTillFieldDisplay.datepicker({
                        dateFormat: "dd/mm/yy",
                        altField: dateTillFieldAlt,
                        altFormat: "yy-mm-dd",
                        minDate: new Date(),
                        // defaultDate: dateTillFieldDisplay.val() ? dateTillFieldDisplay.val() : null,
                        onSelect: function(dateText, datePicker){
                            dateTillFieldDisplay.val(dateText);
                            dateTillFieldAlt.change();
                        }
                    });
                    if(datepickerFromElem.length)
                    // if(dateFromFieldDisplay.length)
                    {
                        dateTillFieldAlt.on("change", function(){
                            const maxDate = new Date(this.value);
                            datepickerFromElem.datepicker("option", "maxDate", maxDate);
                            // dateFromFieldDisplay.datepicker("option", "maxDate", maxDate);
                        });
                    }
                }
                dateTillFieldDisplay.on("focus", function(){
                    datepickerTillElem.removeClass("d-none");
                });
                dateTillFieldDisplay.on("blur", function(){
                    setTimeout(function(){
                        datepickerTillElem.addClass("d-none");
                    }, 500);
                });
    
            });
        });
    },

    initEntityListView: function(view)
    {
        const listTitleElem = view.find(".list-title");
        const listFiltersElem = view.find(".list-filters");
        const listSummaryElem = view.find(".list-summary");
        const listItemsElem = view.find(".list-items");
        const listPaginationElem = view.find(".list-pagination");

        const filtersForm = listFiltersElem.find("form.filters-form");
        const pagedInput = filtersForm.find("input[name='paged']");

        filtersForm.on("ofrp_submit_ajax_form_std_before", function(e, data){
            view.addClass("loading");

            // const reqArgs = filtersForm.serialize();
            // const reqUri = window.location.pathname + "?" + reqArgs;

            // window.history.pushState({
            //     additionalInformation: 'Updated the URL with JS'
            // }, document.title, reqUri);
        });

        filtersForm.on("ofrp_submit_ajax_form_std_after", function(e, resp, data){
            
            if(resp.status && typeof resp.values !== 'undefined')
            {
                if(typeof resp.values.title_html !== "undefined")
                {
                    listTitleElem.html(resp.values.title_html);
                }
                if(typeof resp.values.filters_html !== "undefined")
                {
                    listFiltersElem.html(resp.values.filters_html);
                }
                if(typeof resp.values.summary_html !== "undefined")
                {
                    listSummaryElem.html(resp.values.summary_html);
                }
                if(typeof resp.values.list_html !== "undefined")
                {
                    listItemsElem.html(resp.values.list_html);
                }
                if(typeof resp.values.pager_html !== "undefined")
                {
                    listPaginationElem.html(resp.values.pager_html);
                }
            }
            view.removeClass("loading");
        });

        listPaginationElem.on("click", ".view.list-pager.ajax-pager li.page a", function(e){
            e.preventDefault();

            pagedInput.val(parseInt(jQuery(this).data("page")));
            pagedInput.change();
        });
    }
};