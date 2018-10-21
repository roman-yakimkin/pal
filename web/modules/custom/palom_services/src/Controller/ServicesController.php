<?php

namespace Drupal\palom_services\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Plugin\views\filter\Access;
use Drupal\palom_services\PalomServices;
use Drupal\palom_services\Form\ServicesForm;

class ServicesController extends ControllerBase {

    // Функция для проверки на корректность названия службы
    public function access(AccountInterface $account, $service_path){
        $condition = (PalomServices::getServiceType($service_path) != null);
        return AccessResult::allowedIf(true);
    }

    // Получить информацию по службе определенного типа
    public function getServices($service_path){
        $service_type = PalomServices::getServiceType($service_path);
        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_services\Form\ServicesForm', $service_type);

        return $data;
    }

    public function getServicesAll(){
        $data['form'] = \Drupal::formBuilder()->getForm('\Drupal\palom_services\Form\ServicesForm');

        return $data;
    }
}