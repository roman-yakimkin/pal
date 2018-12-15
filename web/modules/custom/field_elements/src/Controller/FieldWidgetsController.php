<?php

namespace Drupal\field_elements\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\field_elements\FieldWidgets;
use Symfony\Component\HttpFoundation\JsonResponse;

class FieldWidgetsController extends ControllerBase {

    // Regions and cities by one country
    public function getRegionCityByGeo($geo_id){
        return new JsonResponse(FieldWidgets::getRegionCityByGeo($geo_id));
    }

    // Regions, cities and sacred places by one country
    public function getRegionCityPlaceByGeo($geo_id){
        $jr = new JsonResponse(FieldWidgets::getRegionCityPlaceByGeo($geo_id));
        return $jr;
    }
}