<?php

namespace Drupal\palom_advert\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AdvertAutocomplete {

    // A list of countries, regions and sacred places by autocomplete
    public function autocompleteTo(Request $request){

        $string = $request->query->get('q');

        $matches = [];

        if ($string) {
            $conn = Database::getConnection();

            /*
             *  A list of countries
             */
            $q_countries = $conn->select('taxonomy_term_field_data', 'country');
            $q_countries->addField('country', 'tid', 'id');
            $q_countries->condition('country.vid', 'countries');

            $q_countries->addField('country', 'name', 'object_name');
            $q_countries->condition('country.name', '%'.$q_countries->escapeLike($string).'%', 'LIKE');

            $q_countries->addExpression(':city_type_name_c', 'city_type_name', [':city_type_name_c' => '']);
            $q_countries->addExpression(':geo_name_1_c', 'geo_name_1', [':geo_name_1_c' => '']);
            $q_countries->addExpression(':geo_name_2_c', 'geo_name_2', [':geo_name_2_c' => '']);
            $q_countries->addExpression(':city_name_c', 'city_name', [':city_name_c' => '']);
            $q_countries->addExpression(':object_type_c', 'object_type', [':object_type_c' => 'country']);

            $q_countries->innerJoin('taxonomy_term_hierarchy', 'tth', 'country.tid = tth.tid');
            $q_countries->condition('tth.parent', 0);

            /*
             *  A list of regions
             */
            $q_cities = $conn->select('node_field_data', 'city');
            $q_cities->addField('city', 'nid', 'id');
            $q_cities->addField('city', 'title', 'object_name');
            $q_cities->condition('city.title', '%'.$q_cities->escapeLike($string).'%', 'LIKE');
            $q_cities->condition('city.type', 'city');
            $q_cities->condition('city.status', 1);

            $q_cities->leftJoin('node__field_city_type', 'field_city_type', 'city.nid = field_city_type.entity_id');
            $q_cities->leftJoin('taxonomy_term_field_data', 'city_type', 'field_city_type.field_city_type_target_id = city_type.tid');
            $q_cities->addField('city_type', 'name', 'city_type_name');

            // Country and region
            $q_cities->leftJoin('node__field_country', 'field_geo_1', 'city.nid = field_geo_1.entity_id');
            $q_cities->leftJoin('taxonomy_term_field_data', 'geo_1', 'field_geo_1.field_country_target_id = geo_1.tid');
            $q_cities->addField('geo_1', 'name', 'geo_name_1');

            $q_cities->leftJoin('taxonomy_term_hierarchy', 'tth', 'geo_1.tid = tth.tid');
            $q_cities->leftJoin('taxonomy_term_field_data', 'geo_2', 'tth.parent = geo_2.tid');
            $q_cities->addField('geo_2', 'name', 'geo_name_2');

            $q_cities->addExpression(':city_name', 'city_name', [':city_name' => '']);
            $q_cities->addExpression(':city_type', 'object_type', [':city_type' => 'city']);

            /*
             * A list of sacred places
             */
            $q_places = $conn->select('node_field_data', 'place');
            $q_places->addField('place', 'nid', 'id');
            $q_places->addField('place', 'title', 'object_name');
            $q_places->condition('place.title', '%'.$q_places->escapeLike($string).'%', 'LIKE');
            $q_places->condition('place.type', 'sacred_place');
            $q_places->condition('place.status', 1);

            // City
            $q_places->leftJoin('node__field_city', 'field_city', 'place.nid = field_city.entity_id');
            $q_places->leftJoin('node_field_data', 'city', 'field_city.field_city_target_id = city.nid');
            $q_places->condition('city.status', 1);

            // Type of the city
            $q_places->leftJoin('node__field_city_type', 'field_city_type', 'city.nid = field_city_type.entity_id');
            $q_places->leftJoin('taxonomy_term_field_data', 'city_type', 'field_city_type.field_city_type_target_id = city_type.tid');
            $q_places->addField('city_type', 'name', 'city_type_name');

            // Country and region
            $q_places->leftJoin('node__field_country', 'field_geo_1', 'place.nid = field_geo_1.entity_id');
            $q_places->leftJoin('taxonomy_term_field_data', 'geo_1', 'field_geo_1.field_country_target_id = geo_1.tid');
            $q_places->addField('geo_1', 'name', 'geo_name_1');

            $q_places->leftJoin('taxonomy_term_hierarchy', 'tth', 'geo_1.tid = tth.tid');
            $q_places->leftJoin('taxonomy_term_field_data', 'geo_2', 'tth.parent = geo_2.tid');
            $q_places->addField('geo_2', 'name', 'geo_name_2');

            $q_places->addField('city', 'title', 'city_name');
            $q_places->addExpression(':place_type', 'object_type', [':place_type' => 'place']);
            /*
             * Collect evetyhing into a common query
             */
            $q = $q_countries->union($q_cities)->union($q_places)->range(0,15);

            $st = $q->__toString();
            $result = $q->execute();

            foreach($result as $row){

                $city_type_name = '';
                $city_name = '';
                $geo_name_1 = '';
                $geo_name_2 = '';

                if ($row->object_type == 'country'){
                    $object_name = $row->object_name;
                }
                elseif ($row->object_type == 'place'){

                    if ($row->city_type_name != NULL)
                        $city_type_name = $row->city_type_name.' ';

                    if ($row->city_name != NULL)
                        $city_name = ' '.$city_type_name.$row->city_name;

                    if ($row->geo_name_1 != NULL)
                        $geo_name_1 = ', '.$row->geo_name_1;

                    if ($row->geo_name_2 != NULL)
                        $geo_name_2 = ', '.$row->geo_name_2;

                    $object_name = $row->object_name.' ( '.$city_name.$geo_name_1.$geo_name_2.' )';
                }
                elseif ($row->object_type == 'city'){

                    if ($row->city_type_name != NULL)
                        $city_type_name = $row->city_type_name.' ';

                    if ($row->geo_name_1 != NULL)
                        $geo_name_1 = $row->geo_name_1;
//                    $geo_name_1 = ', '.$row->geo_name_1;

                    if ($row->geo_name_2 != NULL)
                        $geo_name_2 = ', '.$row->geo_name_2;

                    $object_name = $city_type_name.$row->object_name.' ( '.$geo_name_1.$geo_name_2.' )';
                }
                elseif ($row->object_type == 'country'){

                }

                $value = Html::escape($row->object_name.' ('.$row->object_type.'-'.$row->id.')');
                $label = Html::escape($object_name.' => '.$row->object_type.' => '.$row->id);
                $matches[] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }

        }

        return new JsonResponse($matches);
    }
}
