define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/ui-select'
], function ($, _, uiRegistry, select) {
    'use strict';

    
    
    function setrelatedoptions(object){
        var selectedoptions=object.value();
        var child_attribute_code= object.related_options["child_attribute_code"];
        var related_options_values= object.related_options["related_options"];

        if (related_options_values.length == 0){
            return false;
        }
        
        var targetfield=uiRegistry.get("relating_index = "+child_attribute_code);
        if (!targetfield){
            return false;
        }
        var targetmultiple=targetfield["multiple"];
        var newvalues=[];

        if (!$.isArray(selectedoptions)){
            selectedoptions=[selectedoptions];
        }

        if (selectedoptions.length == 0){
            if (!targetmultiple){
                newvalues="";                    
            }
            targetfield.value(newvalues);
            return false;
        }
        
        var newsinglereloption="";
        $.each(selectedoptions, function(key, optionselected) {
            if(optionselected in related_options_values){
                newsinglereloption=related_options_values[optionselected];
                if($.inArray(newsinglereloption, newvalues) == -1){
                    newvalues.push(newsinglereloption);
                }
            }                
        });
        if (newvalues.length > 0){
            if (!targetmultiple){
                newvalues=newvalues[0];
            }
            targetfield.value(newvalues);
        }
    }
  
	

    return select.extend({	
		
        onUpdate: function (value) {
		   if (this.haschildrelated){
                setrelatedoptions(this);
            }
            if (this.multiple){
                var relatedinput=this.relating_index;
                var targetfield=uiRegistry.get("index = "+relatedinput);
                var targetvalue="";
                if (value.length > 0){
                    targetvalue=value.join();
                }
                targetfield.value(targetvalue);
            }
        }
    });
});