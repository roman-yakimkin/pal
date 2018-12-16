<?php

namespace Drupal\palom_places\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\palom_places\PalomPlaces;

class PlacesController extends ControllerBase {

    // A list of sacred places
    public function places(){
        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_places\Form\PlacesForm');

        return $data;
    }
}