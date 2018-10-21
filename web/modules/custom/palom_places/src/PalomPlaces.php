<?php

namespace Drupal\palom_places;

use Drupal\Core\Database\Database;
use Drupal\Core\Link;

class PalomPlaces {

    // Страна по умолчанию в форме святых мест
    public static function getCountryDefault(){
        return 2;
    }

    // Количество выводимых святых мест
    public static function getCountDefault(){
        return 3;
    }

    // Получить список населенных пунктов по стране и региону
    public static function getCitiesList($geo_id){
        $cities = [];
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');
        $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );
        $q->condition('node.type', 'city');
        $q->condition('node.status', 1);

        // Получить тип населенного пункта
        $q->leftJoin('node__field_city_type', 'ct', 'node.nid = ct.entity_id');
        $q->leftJoin('taxonomy_term_field_data', 'city_type', 'ct.field_city_type_target_id = city_type.tid');

        if ($children == []){

            // Страна без регионов или регион
            $q->condition('fc.field_country_target_id', $geo_id);
        }
        else
        {
            // Страна с регионами
            $q->innerJoin('taxonomy_term_hierarchy', 'tth', 'fc.field_country_target_id = tth.tid');
            $q->condition('tth.parent', $geo_id);
        };

        // Хоть одно святое место должно ссылаться на этот населенный пункт
        $q->innerJoin('node__field_city', 'fcity', 'node.nid = fcity.field_city_target_id');
        $q->innerJoin('node_field_data', 'places', 'fcity.entity_id=places.nid');
        $q->condition('places.type', 'sacred_place');
        $q->condition('places.status', 1);

        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title');
        $q->addField('city_type', 'name', 'city_type_name');
        $q->distinct();
        $q->orderBy('title');
        $result = $q->execute();

        foreach ($result as $row){
            $one_city = $row->title;
            if ($row->city_type_name != '' )
                $one_city = $row->city_type_name.' '.$one_city;
            $cities[$row->id] = $one_city;
        };

        return $cities;
    }

    // Общее количество святых мест
    public static function getPlacesCount($geo_id, $city_ids = []){
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        // Выборка святых мест по населенным пунктам
        if ($city_ids != []){
            $q->innerJoin('node__field_city', 'fcity', 'node.nid = fcity.entity_id');
            $q->condition('fcity.field_city_target_id', $city_ids, 'IN');
        }
        else

            // Если выбраны все города, то выборка по стране/региону
        {
            $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );

            if ($children == []){

                // Страна без регионов или регион
                $q->condition('fc.field_country_target_id', $geo_id);
            }
            else
            {
                // Страна с регионами
                $q->innerJoin('taxonomy_term_hierarchy', 'tth', 'fc.field_country_target_id = tth.tid');
                $q->condition('tth.parent', $geo_id);
            };
        }

        $q->condition('node.type', 'sacred_place');
        $q->addExpression('COUNT(*)');
        $result = $q->execute()->fetchField();

        return $result;
    }

    // Получить список святых мест по населенным пунктам или по региону
    public static function getPlacesList($start, $count, $geo_id, $city_ids = []){
        $places = [];

        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        // Выборка святых мест по населенным пунктам
        if ($city_ids != []){
            $q->innerJoin('node__field_city', 'fcity', 'node.nid = fcity.entity_id');
            $q->condition('fcity.field_city_target_id', $city_ids, 'IN');
        }
        else

        // Если выбраны все города, то выборка по стране/региону
        {
            $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );

            if ($children == []){

                // Страна без регионов или регион
                $q->condition('fc.field_country_target_id', $geo_id);
            }
            else
            {
                // Страна с регионами
                $q->innerJoin('taxonomy_term_hierarchy', 'tth', 'fc.field_country_target_id = tth.tid');
                $q->condition('tth.parent', $geo_id);
            };
        }

        $q->condition('node.type', 'sacred_place');
        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title');
        $q->orderBy('title');
        $q->range($start, $count);
        $result = $q->execute();

        foreach ($result as $row){
            $places[$row->id] = $row->title;
        };

        return $places;

    }

    // Отобразить список святых мест
    public static function getPlaces($start, $count,  $geo_id, $city_ids = []){
        $places = PalomPlaces::getPlacesList($start, $count, $geo_id, $city_ids);
        $options = [
            'absolute' => TRUE,
            'attributes' => [
            ]
        ];

        $items = '';

        foreach ($places as $key => &$one_place){
            $one_place = Link::createFromRoute($one_place, 'entity.node.canonical', ['node' => $key], $options);
            $items .= '<li>'.$one_place->toString().'</li>';
        }

        /*
        return [
            '#theme' => 'item_list',
            '#items' => $places,
            '#attributes' => [
            ],
        ];
        */

        return [
            '#markup' => $items,
        ];

    }

}