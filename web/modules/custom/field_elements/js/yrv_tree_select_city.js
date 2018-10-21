/**
 * Provides a farbtastic colorpicker for the fancier widget.
 */
(function ($, Drupal, settings) {

    'use strict';

    Drupal.behaviors.yrv_tree_select_city = {
        attach: function (context, settings) {

            var timePrev=0, timePrev2 = 0;
            var thisWidgetId = '';
            var fieldName = settings.yrv_tree_select_city_widget.field_name;

            console.log(settings);


            // Обновить дерево по коду страны
            function UpdateTree(country_id){
                $.get('/palom-get-info/get-region-and-city-by-geo/'+country_id, function(data){
                    $.ui.fancytree.getTree('#yrv-tree-select-city-'+fieldName)
                        .reload(data)
                        .done(function(){
                        })
                })
            }

            // Получить активный узел
            function getActiveNode(){
                var node = $('#yrv-tree-select-city-'+fieldName).fancytree("getActiveNode");
                return node;
            }

            // Добавить геообъект в список
            function addGeoObjectToListBox($wrapper, context){

                // Если количество введенных дат не превышает максимальное
                var $select = $wrapper.find(".yrv-tree-select-city-listbox select");
                var max_geo = $select.attr("data-cardinality");
                var cnt_geo = $select[0].options.length;
                var node = getActiveNode();

                var geo_in_list = $wrapper.find(".yrv-tree-select-city-listbox select option[value='"+node.data.elem_id+"']");

                if ((max_geo==-1) || (max_geo>cnt_geo)){

                    // Если этот населенный пункт отсутствует в списке
                    if (geo_in_list.length == 0)
                       $select.append($("<option value='"+node.data.elem_id+"'>"+node.title+" ("+$('select[name=sel_countries] :selected', context).text()+")</option>"));
                }
                else
                {
                    // Отобразить диалог с указанием того, что введено максимальное количество дат
                    $('<div id="yrv-tree-select-dialog">Уже добавлено максимальное количество населенных пунктов ('+max_geo+') для данного поля</div>').dialog({
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
            }

            // Удалить геообъект из списка
            function removeGeoObjectFromListBox($wrapper){
                $wrapper.find(".yrv-tree-select-city-listbox select :selected").remove();
                setHiddenField($wrapper);
            }

            // Заполнить скрытое поле значениями для последующей записи в БД
            function setHiddenField($wrapper){

                var $hiddenField = $wrapper.parent('div').find('input[type=hidden]');
                var aGeoTmp = '';

                $wrapper.find(".yrv-tree-select-city-listbox select option").each(function(){
                    aGeoTmp += this.value+' ';
                })

                $hiddenField.val(aGeoTmp);
            }


            $('body').once('initiale-tree-city-widget').each(function(){

                // Инициализация дерева с регионами и городами
                /*
                var $tree = $('.yrv-tree-select-city-tree', context).easytree({
                    stateChanged: function(nodes, nodesJson){
                        activeNode = getActiveNode(nodes);
                        if (activeNode.type == 'city'){

                            var timeNow = $.now();
                            var $wrapper = $(thisWidgetId);

                            // Срабатывает двойной клик
                            if ((timeNow - timePrev < 500) && (timePrev - timePrev2 > 500)){
                                addGeoObjectToListBox($wrapper, context);
                            }
                            timePrev2 = timePrev;
                            timePrev = timeNow;

                        }
                    },
                });
                */

                console.log('#yrv-tree-select-city-'+fieldName);
                $('#yrv-tree-select-city-'+fieldName).fancytree({
                    source:[],
                    dblclick: function(evt, data){
                        var node = data.node;
                        var $wrapper = $('#yrv-tree-select-city-widget-'+fieldName);
                        addPlaceToListBox($wrapper, context);
                    },
                });

                var aData = settings.yrv_tree_select_place_widget;


                var aData = settings.yrv_tree_select_city_widget;

                // Инициализация списка стран
                var countries = aData.countries;
                $.each(countries, function(index, value){
                    $('select[name="sel_countries"]', context).append('<option value="'+value.tid+'">'+value.name+'</option>');
                });

                // Россия по умолчанию
                $('select[name="sel_countries"] option[value="2"]', context).attr('selected', 'selected');
                UpdateTree(2);

                $('select[name="sel_countries"]', context).on('change', function(evt){
                    UpdateTree($(this).val());
                });

                // Инициализация списка уже добавленных населенных пунктов
                for (var aFieldName in aData){
                    var aObjects = aData[aFieldName]['objects'];
                    thisWidgetId = "#yrv-tree-select-city-widget-"+aFieldName;

                    $.each(aObjects, function(index, obj){
                        $(thisWidgetId + " .yrv-tree-select-city-listbox select").append($("<option value='"+obj.elem_id+"'>"+obj.name+"</option>"));
                    })

                    var $wrapper = $('#yrv-tree-select-city-widget-'+aFieldName);
                    setHiddenField($wrapper);
                    $(thisWidgetId + " .yrv-tree-select-city-listbox select").attr("data-cardinality", aData[aFieldName]['cardinality']);
                }
            });

            // По двойному нажатию на элемент списка удаление населенного пункта
            $(".yrv-tree-select-city-listbox select", context).on("dblclick", function(){
//                var $wrapper = $(this).closest('.yrv-tree-select-city-widget');
                var $wrapper = $('#yrv-tree-select-city-widget-'+fieldName);
                removeGeoObjectFromListBox($wrapper, context);
            });

            // Добавление населенного пункта по нажатию на кнопку
            $(".yrv-button-add-city", context).on("click", function(){
                var node = getActiveNode();
                if (node.type == 'city'){
                    var $wrapper = $('#yrv-tree-select-city-widget-'+fieldName);
                    addGeoObjectToListBox($wrapper, context);
                }
                return false;
            });

            // Удаление населенного пункта по нажатию на кнопку
            $(".yrv-button-remove-city", context).on("click", function(){
                var $wrapper = $(this).closest('.yrv-tree-select-city-widget');
                removeGeoObjectFromListBox($wrapper);
                return false;
            });
        }
    };
})(jQuery, Drupal, drupalSettings);