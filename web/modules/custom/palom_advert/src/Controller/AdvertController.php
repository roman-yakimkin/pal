<?php

namespace Drupal\palom_advert\Controller;

use Drupal\Core\Controller\ControllerBase;

class AdvertController extends ControllerBase {

    // Отображение списка поездок
    public function adverts(){
        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_advert\Form\AdvertForm');

        return $data;
    }
}