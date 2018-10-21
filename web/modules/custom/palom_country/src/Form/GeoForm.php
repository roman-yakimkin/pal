<?php

namespace Drupal\palom_country\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\palom_country\PalomCountry;

define(GEO_IS_COUNTRY,1);

class GeoForm extends FormBase {

    public function buildForm(array $form, FormStateInterface $form_state){

        $args = $form_state->getBuildInfo();
        $geo_id = $args['args'][0];

        $form['country'] = [
            '#type' => 'select',
            '#options' => PalomCountry::getCountryList(),
            '#disabled' => TRUE,
        ];


        if (PalomCountry::getGeoType($geo_id) == GEO_IS_COUNTRY){
            $form['country']['#default_value'] = $geo_id;
        }
        else
        {
            $parents = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($geo_id);
            $keys = array_keys($parents);
            $country_id = $keys[0];

            $form['country']['#default_value'] = $country_id;

            $form['region'] = [
                '#type' => 'select',
                '#options' => PalomCountry::getRegionList($country_id),
                '#default_value' => $geo_id,
                '#disabled' => TRUE,
            ];
        }

        $form_state->setCached(FALSE);

        return $form;
    }

    public function getFormId(){
        return 'geo_form';
    }

    public function submitForm(array &$form, FormStateInterface $form_state){

    }
}