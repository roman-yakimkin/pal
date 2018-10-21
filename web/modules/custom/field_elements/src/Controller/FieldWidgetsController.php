<?php

namespace Drupal\field_elements\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\field_elements\FieldWidgets;
use Symfony\Component\HttpFoundation\JsonResponse;

class FieldWidgetsController extends ControllerBase {

    // Регионы и населенные пункты по одной стране
    public function getRegionCityByGeo($geo_id){
        return new JsonResponse(FieldWidgets::getRegionCityByGeo($geo_id));
    }

    // Регионы, населенные пункты и святые места по одной стране
    public function getRegionCityPlaceByGeo($geo_id){
        $jr = new JsonResponse(FieldWidgets::getRegionCityPlaceByGeo($geo_id));
        return $jr;
    }
}