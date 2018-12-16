<?php

namespace Drupal\palom_services;

use Drupal\Core\Database\Database;
use Drupal\Core\Link;


class PalomServices {

    public static function getServiceTypes(){
        return ['piligrimage_service', 'transport_service', 'housing_service', 'feeding_service', 'guide_service'];
    }

    // Amount of output companies
    public static function getCountDefault(){
        return 1;
    }

    // The type of company
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

    // The default country in the company form
    public static function getCountryDefault(){
        return 2;
    }

    // Get a list of cities by a country and a region which has companies
    public static function getCitiesList($geo_id, $service_types){
        $cities = [];
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');
        $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );
        $q->condition('node.type', 'city');
        $q->condition('node.status', 1);

        // Get the type of city
        $q->leftJoin('node__field_city_type', 'ct', 'node.nid = ct.entity_id');
        $q->leftJoin('taxonomy_term_field_data', 'city_type', 'ct.field_city_type_target_id = city_type.tid');

        if ($children == []){

            // A country without regions or a region
            $q->condition('fc.field_country_target_id', $geo_id);
        }
        else
        {
            // A country with regions
            $q->innerJoin('taxonomy_term_hierarchy', 'tth', 'fc.field_country_target_id = tth.tid');
            $q->condition('tth.parent', $geo_id);
        };

        // As least one company should refer to this city
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

    // A list of companies, which give piligrimage services in this region or by cities
    public static function getServicesGeoCount($service_type, $geo_id, $city_ids = []){
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        // A selection of companies by cities
        if ($city_ids != []){
            $q->innerJoin('node__field_city', 'fcity', 'node.nid = fcity.entity_id');
            $q->condition('fcity.field_city_target_id', $city_ids, 'IN');
        }
        else

            // If all the cities are selected then selection by the country/region
        {
            $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );

            if ($children == []){

                // A country without regions or a region
                $q->condition('fc.field_country_target_id', $geo_id);
            }
            else
            {
                // A country with regions
                $q->innerJoin('taxonomy_term_hierarchy', 'tth', 'fc.field_country_target_id = tth.tid');
                $q->condition('tth.parent', $geo_id);
            };
        }

        $q->condition('node.type', $service_type, 'IN');

        $q->addExpression('COUNT(*)');
        $result = $q->execute()->fetchField();

        return $result;
    }

    // A list of companies which give piligrimage service in this region or by cities
    public static function getServicesGeoList($service_type, $start, $count, $geo_id, $city_ids = []){
        $services = [];

        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');

        // A selection of companies by cities
        if ($city_ids != []){
            $q->innerJoin('node__field_city', 'fcity', 'node.nid = fcity.entity_id');
            $q->condition('fcity.field_city_target_id', $city_ids, 'IN');
        }
        else

        // If all the cities are selected then a selection by the country/region
        {
            $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );

            if ($children == []){

                // A country without regions or a region
                $q->condition('fc.field_country_target_id', $geo_id);
            }
            else
            {
                // A country with regions
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

    // The list of companies associated with a sacred place
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

    // A list of companies of all the types in this region or by cities
    public static function getAllServices($start, $count, $geo_id, $city_ids = []){
        return self::getServices(self::getServiceTypes(), $start, $count, $geo_id, $city_ids);
    }

    // Output the list of companies by a geo object
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

    // Output the list of companies by a geo object
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