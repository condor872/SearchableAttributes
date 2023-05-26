define([
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/components/button'
], function (_, uiRegistry, button) {
    'use strict';

	var divid;
	var testdiv;
	
  // call it:
    function updateOptions(data){
        var callurl=data["callurl"];
        var attribute_id=data["attribute_id"];
        var target=data["target"];
        target=uiRegistry.get("index = "+target);
        target.filterInputValue("");
        ajaxcall(callurl,attribute_id,target);
    }

    function ajaxcall(callurl,idattributo,target){
        jQuery.ajax({
            async: "false",
            url: callurl,
            type: "GET",
            data: {attribute_id:idattributo},
            cache: false,
            showLoader: true,
            success: function(response){
                setoptions(response,target);
            }
        })
    }
    function setoptions(response,target){
        var newoptions=response.options;
        target.options(newoptions);
        target.cacheOptions.tree=newoptions;
        target.cacheOptions.plain=newoptions;
        target.cacheOptions.lastOptions=newoptions;
        if (response.therearerelations){
            var relations=response.related_options;
            target.related_options.related_options=relations;
        }
    }
    function redirect(data){
        window.open(data, 'popup','width=1800,height=800,scrollbars=no,resizable=no');
    }
	

    return button.extend({

        /**
         * On value change handler.
         *
         * @param {String} value
		 
		 
         */
		//var scrolling;
		
		
       action: function () {
		   var fai = this.actions;
           //console.log()
           jQuery.each(fai, function(key, value) {
                if (key=="redirect"){
                    redirect(value);
                    return false;
                }
                if (key=="updateOptions"){
                    updateOptions(value);
                    return false;
                }
            });

			    //testdiv= divid.uid;
                
                //window.open(this.actions, '_blank');
				//console.log();
        }
        
    });
});