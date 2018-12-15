/**
 * Provides a farbtastic colorpicker for the fancier widget.
 */
(function ($, Drupal, settings) {

    'use strict';

    Drupal.behaviors.yrv_multi_date = {
        attach: function (context, settings) {

            // Init variables
            var aData = settings.yrv_multi_date_widget;

            // Add a date to the list
            function addCalendarDateToList($wrapper){
                var currentDate = $wrapper.find('.yrv-multi-date-calendar').datepicker("getDate");

                // Add a date into lists if it is ansent.
                var formattedDate = sprintf("%02d.%02d.%04d", currentDate.getDate(), currentDate.getMonth()+1, currentDate.getFullYear());
                var formattedDateValue = sprintf("%04d-%02d-%02d", currentDate.getFullYear(), currentDate.getMonth()+1, currentDate.getDate() );

                // If count of inputed dates not more than max count

                var $select = $wrapper.find(".yrv-multi-date-listbox select");
                var max_dates = $select.attr("data-cardinality");
//                var cnt_dates = $wrapper.find(".yrv-multi-date-listbox select option").size();
                var cnt_dates = $select[0].options.length;


                var date_in_list = $wrapper.find(".yrv-multi-date-listbox select option[value='"+formattedDateValue+"']");

                if ((max_dates==-1) || (max_dates>cnt_dates)){

                    // If data is not it the list
                    if (date_in_list.length == 0)
                        $wrapper.find(".yrv-multi-date-listbox select ").append($("<option value='"+formattedDateValue+"'>"+formattedDate+"</option>"));
                }
                else
                {
                    // Display a warning dialog with max count of datas
                    $('<div id="yrv-multi-date-dialog">Уже добавлено максимальное количество дат ('+max_dates+') для данного поля</div>').dialog({
                        'title': 'Ошибка',
                        'modal': true,
                        buttons: {
                            "Закрыть": function(){
                                $(this).dialog('close');
                            }
                        }
                    });
                }

                setHiddenField($wrapper);

            };

            // Remove a date from the list
            function removeDateFromList($wrapper){
                $wrapper.find(".yrv-multi-date-listbox select :selected").remove();

                setHiddenField($wrapper);
            }

            // Fill a hidden field with values
            function setHiddenField($wrapper){

                var $hiddenField = $wrapper.parent('div').find('input[type=hidden]');
                var aDatesTmp = '';

                $wrapper.find(".yrv-multi-date-listbox select option").each(function(){
                    aDatesTmp += this.value+' ';
                })

                $hiddenField.val(aDatesTmp);
            }

            // Primary initialization of data lists (it's necessary to execute in once)
            $('body').once('init-data').each(function(){

                var timePrev=0, timePrev2 = 0;

                $('.yrv-multi-date-calendar').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    dateFormat: 'dd.mm.yy',
                    onSelect: function(date, inst){
                        var timeNow = $.now();
                        var $wrapper = $(this).closest('.yrv-multi-date-widget');

                        // Perform mouse doucle click
                        if ((timeNow - timePrev < 500) && (timePrev - timePrev2 > 500)){
                            addCalendarDateToList($wrapper);
                        }
                        timePrev2 = timePrev;
                        timePrev = timeNow;
                    }
                });

                for (var aFieldName in aData){
                    var aDates = aData[aFieldName]['dates'];

                    for (var i=0; i<aDates.length; i++){
                        var aDate = new Date(aDates[i]);
                        var formattedDate = sprintf("%02d.%02d.%04d", aDate.getDate(), aDate.getMonth()+1, aDate.getFullYear());
                        var formattedDateValue = sprintf("%04d-%02d-%02d", aDate.getFullYear(), aDate.getMonth()+1, aDate.getDate() );
                        $("#yrv-multi-date-widget-"+aFieldName+" .yrv-multi-date-listbox select").append($("<option value='"+formattedDateValue+"'>"+formattedDate+"</option>"));
                    };
                    var $wrapper = $('#yrv-multi-date-widget-'+aFieldName);
                    setHiddenField($wrapper);
                    $("#yrv-multi-date-widget-"+aFieldName+" .yrv-multi-date-listbox select").attr("data-cardinality", aData[aFieldName]['cardinality']);
                }
            });

            // Double click to delete a date
            $(".yrv-multi-date-listbox select", context).on("dblclick", function(){
                var $wrapper = $(this).closest('.yrv-multi-date-widget');
                removeDateFromList($wrapper);
            });

            // Adding a date via press a button
            $(".yrv-multi-date-button-add-date", context).on("click", function(){
                var $wrapper = $(this).closest('.yrv-multi-date-widget');
                addCalendarDateToList($wrapper);
                return false;
            });

            // Removing a date via press a button
            $(".yrv-multi-date-button-remove-date", context).on("click", function(){
                var $wrapper = $(this).closest('.yrv-multi-date-widget');
                removeDateFromList($wrapper);
                return false;
            });

        }
    };
})(jQuery, Drupal, drupalSettings);