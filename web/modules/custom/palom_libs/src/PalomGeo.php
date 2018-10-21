<?php
/**
 * Created by PhpStorm.
 * User: ROMAN
 * Date: 16.07.2018
 * Time: 19:11
 */

namespace Drupal\palom_libs;

/**
 * Class PalomGeo
 * @package Drupal\palom_libs
 *
 * Вспомогательный класс для работы со странами и регионами
 */
class PalomGeo {

  // Список стран
  public static function getCountryList(){
    $countries = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('countries',0,1);
    foreach($terms as $term){
      $countries[$term->tid] = $term->name;
    }
    return $countries;
  }

  // Список регионов
  public static function getRegionList($country_id){
    $regions = [];
    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadTree('countries',$country_id,2);
    foreach($terms as $term){
      $regions[$term->tid] = $term->name;
    }
    return $regions;
  }

  // Получить тип геообъекта
  public static function getGeoType($geo_id){
    $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($geo_id);

    if ($parents == [])
      return GEO_IS_COUNTRY;
    else
      return GEO_IS_REGION;
  }

  // Страна по умолчанию при выборе географии
  public static function getCountryDefault(){
    return 2;
  }

}