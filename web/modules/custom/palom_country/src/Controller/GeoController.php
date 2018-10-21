<?php

namespace Drupal\palom_country\Controller;

use Drupal\Core\Config\Entity\Query\Query;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\palom_country\PalomCountry;
use Drupal\palom_places\PalomPlaces;

class GeoController extends ControllerBase {

    // Показать список населенных пунктов по региону
    public function cities($geo_id){

        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_country\Form\GeoForm', $geo_id);
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        // Значения для пагинатора
        $limit = 5;

        if (empty($_REQUEST['page'])) {
            $start = 0;
        }
        else {
            $start = $_REQUEST['page'] * $limit;
        }

        // Получить список населенных пунктов по данной стране или региону
        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');
        $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );
        $q->condition('node.type', 'city');

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

        $q_count = clone $q;
        $q_count->addExpression('COUNT(*)');
        $total = $q_count->execute()->fetchField();

        pager_default_initialize($total, $limit);

        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title');
        $q->addField('city_type', 'name', 'city_type_name');
        $q->orderBy('title');
        $q->range($start, $limit);
        $result = $q->execute();

        $items = [];

        foreach ($result as $row){
            $one_city = $row->title;
            if ($row->city_type_name != '' )
                $one_city = $row->city_type_name.' '.$one_city;
            $options = [
                'absolute' => TRUE,
                'attributes' => [
                ]
            ];
            $items[] = Link::createFromRoute($one_city, 'entity.node.canonical', ['node' => $row->id], $options);
        };

        $data['cities'] = [
            '#theme' => 'item_list',
            '#items' => $items,
            '#attributes' => [
            ],
            '#prefix' => '<div id="geo-cities">',
            '#suffix' => '</div>',
        ];

        $data['pager'] = [
            '#type' => 'pager',
        ];

        $data['#attached']['library'][] = 'palom_country/palom_country';

        return $data;
    }

    public function test(){

        $cities_val = PalomPlaces::getCitiesList(2);

        $cities = [];

        foreach($cities_val as $key=>$value){
            $cities['city_'.$key] = $value;
        };

        kint($cities);


        $data['test'] = [
            '#type' => 'markup',
            '#markup' => 'hello, world !!!',
        ];

        return $data;
    }

    // Список святых мест по одному геообъекту
    public function places($geo_id){
        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_country\Form\GeoForm', $geo_id);
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        // Значения для пагинатора
        $limit = 5;

        if (empty($_REQUEST['page'])) {
            $start = 0;
        }
        else {
            $start = $_REQUEST['page'] * $limit;
        }

        // Получить список святых мест по данной стране или региону
        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');
        $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );
        $q->condition('node.type', 'sacred_place');

        // Населенный пункт
        $q->leftJoin('node__field_city', 'field_city', 'node.nid = field_city.entity_id');
        $q->leftJoin('node_field_data', 'node_city', 'field_city.field_city_target_id = node_city.nid');

        // Тип населенного пункта
        $q->leftJoin('node__field_city_type', 'ct', 'node_city.nid = ct.entity_id');
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

        $q_count = clone $q;
        $q_count->addExpression('COUNT(*)');
        $total = $q_count->execute()->fetchField();

        pager_default_initialize($total, $limit);

        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title', 'place_name');
        $q->addField('node_city', 'nid', 'city_id');
        $q->addField('node_city', 'title', 'city_name');
        $q->addField('city_type', 'name', 'city_type_name');
        $q->orderBy('place_name');
        $q->range($start, $limit);

        $result = $q->execute();

        $items = [];

        foreach ($result as $row){

            $one_place = $row->place_name;
            $options = [
                'absolute' => TRUE,
                'attributes' => [
                ]
            ];
            $place_name = Link::createFromRoute($one_place, 'entity.node.canonical', ['node' => $row->id], $options)->toString();

            if (!is_null($row->city_id)){
                $one_city = $row->city_name;
                if ($row->city_type_name != '' )
                    $one_city = $row->city_type_name.' '.$one_city;

                $city_link = Link::createFromRoute($one_city, 'entity.node.canonical', ['node' => $row->city_id], $options)->toString();

                $place_name = [
                    '#type' => 'markup',
                    '#markup' => $place_name.' ('.$city_link.')',
                ];
            }

            $items[] = $place_name;
        };

        $data['places'] = [
            '#theme' => 'item_list',
            '#items' => $items,
            '#attributes' => [
            ],
            '#prefix' => '<div id="geo-places">',
            '#suffix' => '</div>',
        ];

        $data['pager'] = [
            '#type' => 'pager',
        ];

        $data['#attached']['library'][] = 'palom_country/palom_country';

        return $data;

    }

    // Список организаций по одному геообъекту
    public function services($geo_id){
        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_country\Form\GeoForm', $geo_id);
        $children = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($geo_id);

        // Значения для пагинатора
        $limit = 2;

        if (empty($_REQUEST['page'])) {
            $start = 0;
        }
        else {
            $start = $_REQUEST['page'] * $limit;
        }

        // Получить список организаций по данной стране или региону
        $conn = Database::getConnection();
        $q = $conn->select('node_field_data', 'node');
        $q->innerJoin('node__field_country', 'fc', 'node.nid = fc.entity_id' );
        $q->condition('node.type', ['housing_service', 'feeding_service', 'transport_service', 'guide_service'], 'IN');

        // Населенный пункт
        $q->leftJoin('node__field_city', 'field_city', 'node.nid = field_city.entity_id');
        $q->leftJoin('node_field_data', 'node_city', 'field_city.field_city_target_id = node_city.nid');

        // Тип населенного пункта
        $q->leftJoin('node__field_city_type', 'ct', 'node_city.nid = ct.entity_id');
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

        $q_count = clone $q;
        $q_count->addExpression('COUNT(*)');
        $total = $q_count->execute()->fetchField();

        pager_default_initialize($total, $limit);

        $q->addField('node', 'nid', 'id');
        $q->addField('node', 'title', 'service_name');
        $q->addField('node_city', 'nid', 'city_id');
        $q->addField('node_city', 'title', 'city_name');
        $q->addField('city_type', 'name', 'city_type_name');
        $q->orderBy('service_name');
        $q->range($start, $limit);

        $result = $q->execute();

        $items = [];

        foreach ($result as $row){

            $one_service = $row->service_name;
            $options = [
                'absolute' => TRUE,
                'attributes' => [
                ]
            ];
            $service_name = Link::createFromRoute($one_service, 'entity.node.canonical', ['node' => $row->id], $options)->toString();

            if (!is_null($row->city_id)){
                $one_city = $row->city_name;
                if ($row->city_type_name != '' )
                    $one_city = $row->city_type_name.' '.$one_city;

                $city_link = Link::createFromRoute($one_city, 'entity.node.canonical', ['node' => $row->city_id], $options)->toString();

                $service_name = [
                    '#type' => 'markup',
                    '#markup' => $service_name.' ('.$city_link.')',
                ];
            }

            $items[] = $service_name;
        };

        $data['places'] = [
            '#theme' => 'item_list',
            '#items' => $items,
            '#attributes' => [
            ],
            '#prefix' => '<div id="geo-services">',
            '#suffix' => '</div>',
        ];

        $data['pager'] = [
            '#type' => 'pager',
        ];

        $data['#attached']['library'][] = 'palom_country/palom_country';

        return $data;

    }
}