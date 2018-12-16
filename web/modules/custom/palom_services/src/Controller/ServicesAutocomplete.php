<?php

namespace Drupal\palom_services\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class ServicesAutocomplete {

    // Возвращение списка святых мест по автозавершению Returns the list of sacred places by aotocompetion
    public function autocompletePlaces(Request $request){

        $string = $request->query->get('q');

        $matches = [];

        if ($string) {
            $conn = Database::getConnection();
            $q = $conn->select('node_field_data', 'place');
            $q->addField('place', 'nid');
            $q->addField('place', 'title', 'place_name');
            $q->condition('place.title', '%'.$string.'%', 'LIKE');
            $q->condition('place.type', 'sacred_place');
            $q->condition('place.status', 1);

            // A city
            $q->leftJoin('node__field_city', 'field_city', 'place.nid = field_city.entity_id');
            $q->leftJoin('node_field_data', 'city', 'field_city.field_city_target_id = city.nid');
            $q->condition('city.status', 1);
            $q->addField('city', 'title', 'city_name');

            // The type of a city
            $q->leftJoin('node__field_city_type', 'field_city_type', 'city.nid = field_city_type.entity_id');
            $q->leftJoin('taxonomy_term_field_data', 'city_type', 'field_city_type.field_city_type_target_id = city_type.tid');
            $q->addField('city_type', 'name', 'city_type_name');

            // A country and a region
            $q->leftJoin('node__field_country', 'field_geo_1', 'place.nid = field_geo_1.entity_id');
            $q->leftJoin('taxonomy_term_field_data', 'geo_1', 'field_geo_1.field_country_target_id = geo_1.tid');
            $q->addField('geo_1', 'name', 'geo_name_1');

            $q->leftJoin('taxonomy_term_hierarchy', 'tth', 'geo_1.tid = tth.tid');
            $q->leftJoin('taxonomy_term_field_data', 'geo_2', 'tth.parent = geo_2.tid');
            $q->addField('geo_2', 'name', 'geo_name_2');

            $q->range(0,10);

            $result = $q->execute();

            foreach($result as $row){

                $city_type_name = '';
                $city_name = '';
                $geo_name_1 = '';
                $geo_name_2 = '';

                if ($row->city_type_name != NULL)
                    $city_type_name = $row->city_type_name.' ';

                if ($row->city_name != NULL)
                    $city_name = ' '.$city_type_name.$row->city_name;

                if ($row->geo_name_1 != NULL)
                    $geo_name_1 = ', '.$row->geo_name_1;

                if ($row->geo_name_2 != NULL)
                    $geo_name_2 = ', '.$row->geo_name_2;

                $place_name = $row->place_name.' ( '.$city_name.$geo_name_1.$geo_name_2.' )';

                $value = Html::escape($row->place_name.' ('.$row->nid.')');
                $label = Html::escape($place_name);
                $matches[] = [
                    'value' => $value,
                    'label' => $label,
                ];
            }

            return new JsonResponse($matches);
        }

    }
}
