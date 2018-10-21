<?php

namespace Drupal\palom_services;

use Drupal\Core\Database\Database;
use Drupal\Core\Link;


class PalomServices {

    public static function getServiceTypes(){
        return ['piligrimage_service', 'transport_service', 'housing_service', 'feeding_service', 'guide_service'];
    }

    // Количество выводимых служб
    public static function getCountDefault(){
        return 1;
    }

    // Тип организации
    public static function getServiceType($service_path){
        $service_types = [
            'piligrimage' => 'piligrimage_service',
            'transport' => 'transport_service',
            'housing' => 'housing_service',
            'feeding' => 'feeding_service',
            'guide' => 'guide_service',
        ];

        return $service_types[$service_path] ? $service_types[$service_path] : null;
    }

    // Страна по умолчанию в форме организаций
    public static function getCountryDefault(){
        return 2;
    }

    // Получить список населенных пунктов по стране и региону, в которых есть организации
    public static function getCitiesList($geo_id, $service_types){
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

        // Хоть одна организация должна ссылаться на этот населенный пункт
        $q->innerJoin('node__field_city', 'fcity', 'node.nid = fcity.field_city_target_id');
        $q->innerJoin('node_field_data', 'services', 'fcity.entity_id=services.nid');
        $q->condition('services.type', $service_types, 'IN');
        $q->condition('services.status', 1);


        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title');
        $q->addField('city_type', 'name', 'city_type_name');
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

    // Список организаций, предоставляющих паломнические услуги в данном регионе или по городам
    public static function getServicesGeoCount($service_type, $geo_id, $city_ids = []){
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        // Выборка организаций по населенным пунктам
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

        $q->condition('node.type', $service_type, 'IN');

        $q->addExpression('COUNT(*)');
        $result = $q->execute()->fetchField();

        return $result;
    }

    // Список организаций, предоставляющих паломнические услуги в данном регионе или по городам
    public static function getServicesGeoList($service_type, $start, $count, $geo_id, $city_ids = []){
        $services = [];

        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        // Выборка организаций по населенным пунктам
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

        $q->condition('node.type', $service_type, 'IN');

        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title');
        $q->orderBy('title');
        $q->range($start, $count);

        $result = $q->execute();

        foreach ($result as $row){
            $services[$row->id] = $row->title;
        };

        return $services;
    }

    public static function getServicesByPlaceCount($service_type, $place_id){
        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        $q->innerJoin('node__field_sacred_places', 'place', 'node.nid = place.entity_id');
        $q->condition('place.field_sacred_places_target_id', $place_id);

        $q->condition('node.type', $service_type, 'IN');
        $q->condition('node.status', 1);

        $q->addExpression('COUNT(*)');
        $result = $q->execute()->fetchField();

        return $result;
    }

    // Список организаций, связанных с конкретным святым местом
    public static function getServicesByPlaceList($service_type, $start, $count, $place_id){
        $services = [];

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        $q->innerJoin('node__field_sacred_places', 'place', 'node.nid = place.entity_id');
        $q->condition('place.field_sacred_places_target_id', $place_id);

        $q->condition('node.type', $service_type, 'IN');
        $q->condition('node.status', 1);

        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title');
        $q->orderBy('title');
        $q->range($start, $count);

        $result = $q->execute();

        foreach ($result as $row){
            $services[$row->id] = $row->title;
        };

        return $services;
    }

    // Список организаций всех типов в данном регионе или по городам
    public static function getAllServices($start, $count, $geo_id, $city_ids = []){
        return self::getServices(self::getServiceTypes(), $start, $count, $geo_id, $city_ids);
    }

    // Отобразить список организаций по географическому объекту
    public static function getServicesGeo($service_type, $start, $count, $geo_id, $city_ids = []){
        $entities = PalomServices::getServicesGeoList($service_type, $start, $count, $geo_id, $city_ids);

        $options = [
            'absolute' => TRUE,
            'attributes' => [
            ]
        ];

        $items = '';

        foreach ($entities as $key => &$one_entity){
            $one_entity = Link::createFromRoute($one_entity, 'entity.node.canonical', ['node' => $key], $options);
            $items .= '<li>'.$one_entity->toString().'</li>';

        }

        return [
            '#markup' => $items,
        ];
    }

    // Отобразить список организаций по географическому объекту
    public static function getServicesByPlace($service_type, $start, $count, $place_id){
        $entities = PalomServices::getServicesByPlaceList($service_type, $start, $count, $place_id);

        $options = [
            'absolute' => TRUE,
            'attributes' => [
            ]
        ];

        $items = '';

        foreach ($entities as $key => &$one_entity){
            $one_entity = Link::createFromRoute($one_entity, 'entity.node.canonical', ['node' => $key], $options);
            $items .= '<li>'.$one_entity->toString().'</li>';

        }

        return [
            '#markup' => $items,
        ];
    }

}