define([
    'jquery',
    'underscore',
    'uiRegistry',
    'Magento_Ui/js/form/element/abstract',
    'mage/validation'
  ], function ($, _, uiRegistry, Abstract) {
  'use strict';
  
  
  
   return Abstract.extend({ 
         /*initialize: function (){
         //console.log(value);
         //return 
         var supercomponent = this._super();
       },     */
 
      /**
      * On value change handler.
      *
      * @param {String} value
      */
      onUpdate: function (value) {
            var selectedoptions=value;
            var child_attribute_code= this.related_options["child_attribute_code"];
            var related_options_values= this.related_options["related_options"];

            if (related_options_values.length == 0){
                return false;
            }
            
            var targetfield=uiRegistry.get("index = "+child_attribute_code);
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
    });
 });