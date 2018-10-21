<?php

namespace Drupal\palom_libs;

use Drupal\Core\Database\Database;
use Drupal\Core\Link;
use Drupal\Core\Url;

class PalomLibs {

    public static function getCityTypeAndName($city_id){
        $conn = Database::getConnection();

        $q_cities = $conn->select('node_field_data', 'city');

        $q_cities->addField('city', 'nid', 'id');
        $q_cities->addField('city', 'title', 'name');

        $q_cities->condition('city.nid', $city_id);
        $q_cities->condition('city.type', 'city');
        $q_cities->condition('city.status', 1);

        $q_cities->leftJoin('node__field_city_type', 'field_city_type', 'city.nid = field_city_type.entity_id');
        $q_cities->leftJoin('taxonomy_term_field_data', 'city_type', 'field_city_type.field_city_type_target_id = city_type.tid');
        $q_cities->addField('city_type', 'tid', 'city_type_id');
        $q_cities->addField('city_type', 'name', 'city_type_name');

        $result = $q_cities->execute();

        foreach($result as $row){
            $info['id'] = $row->id;
            $info['name'] = $row->name;
            $info['type_id'] = $row->city_type_id;
            $info['type_name'] = $row->city_type_name;

            $city_name = $row->name;
            $city_type_name = '';

            if ($row->city_type_name != NULL){
                $city_type_name = $row->city_type_name.' ';
            }

            $city = $city_type_name.$city_name;
        }

        return $city;
    }

    // Полная информация о населенном пункте (тип, страна, регион и т.д.)
    public static function getCityInfo($city_id, array $linked_fields = []){
        $info = [];

        $conn = Database::getConnection();

        $q_cities = $conn->select('node_field_data', 'city');

        $q_cities->addField('city', 'nid', 'id');
        $q_cities->addField('city', 'title', 'name');

        $q_cities->condition('city.nid', $city_id);
        $q_cities->condition('city.type', 'city');
        $q_cities->condition('city.status', 1);

        $q_cities->leftJoin('node__field_city_type', 'field_city_type', 'city.nid = field_city_type.entity_id');
        $q_cities->leftJoin('taxonomy_term_field_data', 'city_type', 'field_city_type.field_city_type_target_id = city_type.tid');
        $q_cities->addField('city_type', 'tid', 'city_type_id');
        $q_cities->addField('city_type', 'name', 'city_type_name');

        // Страна и регион
        $q_cities->leftJoin('node__field_country', 'field_geo_1', 'city.nid = field_geo_1.entity_id');
        $q_cities->leftJoin('taxonomy_term_field_data', 'geo_1', 'field_geo_1.field_country_target_id = geo_1.tid');
        $q_cities->addField('geo_1', 'tid', 'geo_id_1');
        $q_cities->addField('geo_1', 'name', 'geo_name_1');

        $q_cities->leftJoin('taxonomy_term_hierarchy', 'tth', 'geo_1.tid = tth.tid');
        $q_cities->leftJoin('taxonomy_term_field_data', 'geo_2', 'tth.parent = geo_2.tid');
        $q_cities->addField('geo_2', 'tid', 'geo_id_2');
        $q_cities->addField('geo_2', 'name', 'geo_name_2');

        $result = $q_cities->execute();

        foreach($result as $row){
            $info['id'] = $row->id;
            $info['name'] = $row->name;
            $info['type_id'] = $row->city_type_id;
            $info['type_name'] = $row->city_type_name;

            if ($row->geo_id_2 != NULL){
                $info['region_id'] = $row->geo_id_1;
                $info['region_name'] = $row->geo_name_1;

                $info['country_id'] = $row->geo_id_2;
                $info['country_name'] = $row->geo_name_2;
            }
            else{
                $info['country_id'] = $row->geo_id_1;
                $info['country_name'] = $row->geo_name_1;
            }

            $city_name = $row->name;
            $city_type_name = '';
            $geo_name_1 = '';
            $geo_name_2 = '';

            $options = ['absolute' => TRUE];

            if (in_array('city', $linked_fields)){
                $url = Url::fromRoute('entity.node.canonical', ['node' => $row->id], $options);
                $link = Link::fromTextAndUrl($row->name, $url);
                $link = $link->toRenderable();
                $link['#attributes'] = array('class' => array('internal'));
                $city_name = render($link);
            };

            if ($row->city_type_name != NULL){

                if (in_array('type', $linked_fields)){
                    $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $row->city_type_id], $options);
                    $link = Link::fromTextAndUrl($row->city_type_name, $url);
                    $link = $link->toRenderable();
                    $link['#attributes'] = array('class' => array('internal'));
                    $city_type_name = render($link).' ';
                }
                else
                   $city_type_name = $row->city_type_name.' ';
            }

            if ($row->geo_name_1 != NULL){
                if (in_array('geo', $linked_fields)){
                    $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $row->geo_id_1], $options);
                    $link = Link::fromTextAndUrl($row->geo_name_1, $url);
                    $link = $link->toRenderable();
                    $link['#attributes'] = array('class' => array('internal'));
                    $geo_name_1 = render($link);
                }
                else
                    $geo_name_1 = $row->geo_name_1;
            }


            if ($row->geo_name_2 != NULL){
                if (in_array('geo', $linked_fields)){
                    $url = Url::fromRoute('entity.taxonomy_term.canonical', ['taxonomy_term' => $row->geo_id_2], $options);
                    $link = Link::fromTextAndUrl($row->geo_name_2, $url);
                    $link = $link->toRenderable();
                    $link['#attributes'] = array('class' => array('internal'));
                    $geo_name_2 = ', '.render($link);
                }
                else
                    $geo_name_2 = ', '.$row->geo_name_2;
            }

            $info['city_display'] = $city_type_name.$city_name.' ( '.$geo_name_1.$geo_name_2.' )';

            if (in_array('all', $linked_fields)){
                $url = Url::fromRoute('entity.node.canonical', ['node' => $row->id], $options);
                $link = Link::fromTextAndUrl($info['city_display'], $url);
                $link = $link->toRenderable();
                $link['#attributes'] = array('class' => array('internal'));
                $info['city_display'] = render($link);
            }
        }

//        vdp($info);

        return $info;

    }
}