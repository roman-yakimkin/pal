/**
 * Provides a farbtastic colorpicker for the fancier widget.
 */
(function ($, Drupal, settings) {

    'use strict';

    Drupal.behaviors.yrv_tree_select_place = {
        attach: function (context, settings) {
/*
            $('#rro').fancytree({
                source: [  // Typically we would load using ajax instead...
                    {title: "Node 1"},
                    {title: "Node 2"},
                    {title: "Folder 3", folder: true, expanded: true, children: [
                        {title: "Node 3.1", key: "id3.1"},
                        {title: "Node 3.2", selected: true}
                    ]},
                    {title: "Folder 4", folder: true, children: [
                        {title: "Node 4.1"},
                        {title: "Node 4.2"}
                    ]}
                ],
            });
*/

            var timePrev=0, timePrev2 = 0;
            var placesWidgetId = '';
            var fieldName = settings.yrv_tree_select_place_widget.field_name;

            // Update a tree according to id country
            function UpdateTreePlace(country_id){

                $.get('/palom-get-info/get-region-city-place-by-geo/'+country_id, function(data){
                    $.ui.fancytree.getTree('#yrv-tree-select-place-'+fieldName)
                        .reload(data)
                        .done(function(){
                        })
               })
            }

            // Get the active node
            function getActiveNode(){
                var node = $('#yrv-tree-select-place-'+fieldName).fancytree("getActiveNode");
                return node;
            }

            // Add a geoobject into the list
            function addPlaceToListBox($wrapper, context){

                // Если количество введенных дат не превышает максимальное
                var $select = $wrapper.find(".yrv-tree-select-place-listbox select");
                var max_geo = $select.attr("data-cardinality");
                var cnt_geo = $select[0].options.length;
                var node = getActiveNode();

                var geo_in_list = $wrapper.find(".yrv-tree-select-place-listbox select option[value='"+node.data.elem_id+"']");

                if ((max_geo==-1) || (max_geo>cnt_geo)){

                    // Если это святое место отсутствует в списке
                    if (geo_in_list.length == 0){
                        console.log("<option value='"+node.data.elem_id+"'>"+node.title+" "+node.data.geo_str+"</option>");

                        $select.append($("<option value='"+node.data.elem_id+"'>"+node.title+" "+node.data.geo_str+"</option>"));

                    }
                }
                else
                {
                    // Display a warning dialog with max count of dates
                    $('<div id="yrv-tree-select-dialog">Уже добавлено максимальное количество святых мест ('+max_geo+') для данного поля</div>').dialog({
                        'title': 'Error',
                        'modal': true,
                        buttons: {
                            "Close": function(){
                                $(this).dialog('close');
                            }
                        }
                    });
                }

                setPlaceHiddenField($wrapper);
            }

            // Remove a geoobject from the list
            function removePlaceFromListBox($wrapper, context){
                $wrapper.find(".yrv-tree-select-place-listbox select :selected", context).remove();
                setPlaceHiddenField($wrapper);
            }

            // Fill a hidden fields with values
            function setPlaceHiddenField($wrapper){

                var $hiddenField = $wrapper.parent('div').find('input[type=hidden]');
                var aGeoTmp = '';

                $wrapper.find(".yrv-tree-select-place-listbox select option").each(function(){
                    aGeoTmp += this.value+' ';
                });

                $hiddenField.val(aGeoTmp);
            }


            $('body').once().each(function(){

                $('#yrv-tree-select-place-'+fieldName).fancytree({
                    source:[],
                    dblclick: function(evt, data){
                        var node = data.node;
                        var $wrapper = $('#yrv-tree-select-place-widget-'+fieldName);
                        addPlaceToListBox($wrapper, context);
                    },
                });

                var aData = settings.yrv_tree_select_place_widget;

                // Initialization of a country list
                var countries = aData.countries;

                $.each(countries, function(index, value){
                    $('select[name="sel_countries_place"]', context).append('<option value="'+value.tid+'">'+value.name+'</option>');
                });

                // Russia by default
                $('select[name="sel_countries_place"] option[value="2"]', context).attr('selected', 'selected');
                    UpdateTreePlace(2);

                $('select[name="sel_countries_place"]', context).on('change', function(evt){
                    UpdateTreePlace($(this).val());
                });

                // Initialization of the added city list
                for (var aFieldName in aData){
                    var aObjects = aData[aFieldName]['objects'];

                    placesWidgetId = "#yrv-tree-select-place-widget-"+aFieldName;

                    $.each(aObjects, function(index, obj){
                        $(placesWidgetId + " .yrv-tree-select-place-listbox select").append($("<option value='"+obj.elem_id+"'>"+obj.name+"</option>"));
                    })

                    var $wrapper = $('#yrv-tree-select-place-widget-'+aFieldName);
                    setPlaceHiddenField($wrapper);
                    $(placesWidgetId + " .yrv-tree-select-place-listbox select").attr("data-cardinality", aData[aFieldName]['cardinality']);
                }
            });

            // Remove an element by double mouse clicking
            $(".yrv-tree-select-place-listbox select", context).on("dblclick", function(){
                var $wrapper = $('#yrv-tree-select-place-widget-'+fieldName);
                removePlaceFromListBox($wrapper, context);
            });

            // Add a city via press a button
            $(".yrv-button-add-place", context).on("click", function(){
                var node = getActiveNode();
                if (node.type == 'place'){
                    var $wrapper = $('#yrv-tree-select-place-widget-'+fieldName);
                    addPlaceToListBox($wrapper, context);
                }
                return false;
            });

            // Remove a city via press a button
            $(".yrv-button-remove-place", context).on("click", function(){
                var $wrapper = $(this).closest('.yrv-tree-select-place-widget');
                removePlaceFromListBox($wrapper, context);
                return false;
            });
        }
    };
})(jQuery, Drupal, drupalSettings);