<?php

namespace Drupal\field_elements;

use Drupal\Core\Database\Database;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Вспомогательные функции для виджетов поля
 * Class FieldWidjets
 * @package Drupal\field_elements
 */
class FieldWidgets {

    /**
     * Список стран
     * @return mixed
     */
    public static function getCountryList(){
        $conn = Database::getConnection();

        $query = $conn->select('taxonomy_term_field_data', 'ttd');
        $query->fields('ttd', ['tid', 'name']);

        $query->addJoin('inner', 'taxonomy_term_hierarchy', 'tth', 'ttd.tid = tth.tid');
        $query->condition('tth.parent',0);
        $query->condition('ttd.vid', 'countries');
        $query->orderBy('ttd.weight');

        $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

  /**
   * Список регионов по одной стране
   * @param $country_id - id страны
   */
    public static function getRegionList($country_id){
      $conn = Database::getConnection();

      $query = $conn->select('taxonomy_term_field_data', 'ttd');
      $query->fields('ttd', ['tid', 'name']);

      $query->addJoin('inner', 'taxonomy_term_hierarchy', 'tth', 'ttd.tid = tth.tid');
      $query->condition('tth.parent',$country_id);
      $query->condition('ttd.vid', 'countries');
      $query->orderBy('ttd.weight');

      $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

      return $results;
    }

    /**
     * Количество регионов у страны
     * @param $country_id - tid страны
     */
    public static function regionCount($country_id){
        $conn = Database::getConnection();

        $query = $conn->select('taxonomy_term_hierarchy', 'tth' );
        $query->fields('tth', ['tid']);
        $query->condition('tth.parent', $country_id);
        $query->countQuery();
        $results = $query->execute()->fetchField();

        return (int)$results;
    }

    /**
     *  Массив с регионами и городами по стране
     * @param $country_id
     */
    public static function getRegionCityByGeo($country_id){

        $conn = Database::getConnection();

        // Массив регионов и населенных пунктов по стране
        if (self::regionCount($country_id)>0){

            $q_regions = $conn->select('taxonomy_term_field_data', 'ttd');
            $q_regions->addField('ttd', 'tid', 'elem_id');
            $q_regions->addField('ttd', 'name', 'title');
            $q_regions->addJoin('inner', 'taxonomy_term_hierarchy', 'tth', 'ttd.tid = tth.tid');
            $q_regions->condition('tth.parent', $country_id);
            $q_regions->orderBy('ttd.name');

            $results = $q_regions->execute()->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($results as $key => &$value){

                // Рекурсивно обходим список населенных пунктов
                $value['children'] = self::getRegionCityByGeo($value['elem_id']);
                $value['folder'] = true;
                $value['type'] = 'region';
            }

            return $results;
        }

        // Массив населенных пунктов по стране
        else
        {
            $q_cities = $conn->select('node_field_data', 'node');
            $q_cities->addField('node', 'nid', 'elem_id');
            $q_cities->addField('node', 'title', 'title');
            $q_cities->addField('node', 'type', 'type');

            $q_cities->addJoin('inner', 'node__field_country', 'nfc', 'node.nid=nfc.entity_id');
            $q_cities->condition('nfc.field_country_target_id', $country_id);
            $q_cities->condition('node.type', 'city');

            $q_cities->orderBy('node.title');

            $results = $q_cities->execute()->fetchAll(\PDO::FETCH_ASSOC);

            return $results;
        }
    }

    /*
     * Массив с регионами, населенными пунктами и святыми местами по стране
     */
    public static function getRegionCityPlaceByGeo($country_id){
        $geo = self::getRegionCityByGeo($country_id);

        // Перебор по странам с регионами
        if (self::regionCount($country_id)>0){
            $geo_tmp = [];

            foreach ($geo as $key_1 => &$one_geo){

                foreach ($one_geo['children'] as $key_2 => &$one_city){
                    $places = self::getPlacesByCity($one_city['elem_id']);

                    if ($places == []){

                        // Исключить населенный пункт без святых мест
                        unset($one_geo['children'][$key_2]);
                    }
                    else
                    {
                        $one_city['children'] = $places;
                        $one_city['folder'] = true;
                    }
                }
                $one_geo['children'] = array_values($one_geo['children']);

                // Добавить святые места без населенных пунктов
                $places_without_city = self::getPlacesWithoutCity($one_geo['elem_id']);
                foreach($places_without_city as $one_place){
                    $one_geo['children'][] = $one_place;
                };

               // Переупаковать массив
                if (sizeof($one_geo['children'])>0){
                    $one_geo['folder'] = true;
                    $geo_tmp[] = $one_geo;
                }
            }
            $geo = $geo_tmp;
        }
        else

        // Перебор по отдельным городам
        {
            foreach ($geo as $key => &$one_city){
                $places = self::getPlacesByCity($one_city['elem_id']);
                if ($places == []){
                    unset($geo[$key]);
                }
                else
                {
                    $one_city['children'] = $places;
                    $one_city['folder'] = true;
                }
            };


            $places_without_city = self::getPlacesWithoutCity($country_id);
            foreach($places_without_city as $one_place){
                $geo[] = $one_place;
            };


            $geo = array_values($geo);
        }

        return $geo;
    }

    /*
     * Список святых мест по одному населенному пункту
     */
    public static function getPlacesByCity($city_id){
        $conn = Database::getConnection();

        $query = $conn->select('node_field_data', 'node');
        $query->addField('node', 'nid', 'elem_id');
        $query->addField('node', 'title', 'title');
        $query->addJoin('inner', 'node__field_city', 'fc', 'node.nid = fc.entity_id');
        $query->condition('fc.field_city_target_id', $city_id);
        $query->condition('node.type', 'sacred_place');
        $query->condition('node.status', 1);
        $query->orderBy('node.title');

        $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        foreach($results as &$row){
            $row['type'] = 'place';
            $row['geo_str'] = self::getGeoByPlace($row['elem_id'])[0]['geo_str'];
        }

        return $results;
    }

    /*
     * Список святых мест по стране/региону, не привязанных ни к одному населенному пункту
     */
    public static function getPlacesWithoutCity($geo_id){
        $conn = Database::getConnection();

        $query = $conn->select('node_field_data', 'node');
        $query->addField('node', 'nid', 'elem_id');
        $query->addField('node', 'title', 'title');
        $query->addJoin('inner', 'node__field_country', 'fc', 'node.nid = fc.entity_id');
        $query->condition('fc.field_country_target_id', $geo_id);
        $query->condition('node.type', 'sacred_place');
        $query->condition('node.status', 1);

        $subquery = $conn->select('node__field_city', 'nfc');
        $subquery->fields('nfc', ['entity_id']);
        $subquery->condition('nfc.bundle', 'sacred_place');

        $query->condition('node.nid', $subquery, 'NOT IN');
        $query->orderBy('node.title');

        $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
        foreach($results as &$row){
            $row['type'] = 'place';
            $row['geo_str'] = self::getGeoByPlace($row['elem_id'])[0]['geo_str'];
        }

        return $results;
    }

    /*
     * Список населеных пунктов отправления по поездке
     */
    public static function getCitiesByAdvert($advert_id){
        $conn = Database::getConnection();

        $query = $conn->select('node_field_data', 'node');
        $query->addField('node', 'nid', 'elem_id');
        $query->addField('node', 'title', 'title');
        $query->addJoin('node__field_advert_city_from', 'of', 'node.nid = of.field_advert_city_from_target_id');
        $query->condition('of.entity_id', $advert_id);
        $query->orderBy('of.delta');

        $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        return $results;
    }

    // Получить географическую информацию по городу
    public static function getGeoByCity($city_id){
        $conn = Database::getConnection();

        $query=$conn->select('taxonomy_term_field_data', 'tfd_geo2');
        $query->addField('tfd_geo2', 'name', 'name_2');
        $query->addJoin('inner', 'taxonomy_term_hierarchy', 'tth', 'tfd_geo2.tid = tth.tid');
        $query->addJoin('left', 'taxonomy_term_field_data', 'tfd_geo1', 'tth.parent = tfd_geo1.tid');
        $query->addField('tfd_geo1', 'name', 'name_1');
        $query->addJoin('inner', 'node__field_country', 'nfc', 'nfc.field_country_target_id = tfd_geo2.tid');
        $query->condition('nfc.entity_id', $city_id);

        $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as &$value){
            if ($value['name_1'] == '')
                $value['geo_str'] = '('.$value['name_2'].')';
            else
                $value['geo_str'] = '('.$value['name_1'].')';
        }

        return $results;
    }

    // Получить географичесую информацию по святому месту
    public static function getGeoByPlace($place_id){
        $conn = Database::getConnection();

        $query=$conn->select('taxonomy_term_field_data', 'tfd_geo2');
        $query->addField('tfd_geo2', 'name', 'name_2');
        $query->addJoin('inner', 'taxonomy_term_hierarchy', 'tth', 'tfd_geo2.tid = tth.tid');
        $query->addJoin('left', 'taxonomy_term_field_data', 'tfd_geo1', 'tth.parent = tfd_geo1.tid');
        $query->addField('tfd_geo1', 'name', 'name_1');
        $query->addJoin('inner', 'node__field_country', 'nfc', 'nfc.field_country_target_id = tfd_geo2.tid');
        $query->condition('nfc.entity_id', $place_id);

        $results = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($results as &$value){
            if ($value['name_1'] == '')

                // Страна
                $value['geo_str'] = '('.$value['name_2'].')';
            else

                // Страна, Регион
                $value['geo_str'] = '('.$value['name_1'].', '.$value['name_2'].')';
        }

        return $results;
    }
}