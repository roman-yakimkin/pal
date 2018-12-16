<?php

namespace Drupal\palom_places\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InsertCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Ajax;
use Drupal\Core\Render\Element\Checkboxes;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\Core\Url;
use Drupal\palom_country\PalomCountry;
use Drupal\palom_places\PalomPlaces;
use Drupal\Core\Link;

class PlacesForm extends FormBase {

    public function buildForm(array $form, FormStateInterface $form_state){

        if ($form_state->get('element_start') !== NULL)
            $element_start = $form_state->get('element_start');
        else
            $element_start = 0;

        if (!empty($form_state->getValue('country')))
            $country_id = $form_state->getValue('country');
        else
            $country_id = PalomPlaces::getCountryDefault();

        $form['country'] = [
            '#type' => 'select',
            '#options' => PalomCountry::getCountryListContainNodeType(['sacred_place']),
            '#default_value' => $country_id,
            '#ajax' => [
                'callback' => '::changeCountry',
            ]
        ];

        // Output of regions
        $form['region_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'region-wrapper'
            ]
        ];

        $regions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($country_id);

        if ($regions!=[]){
            $form['region_wrapper']['regions'] = [
                '#type' => 'select',
                '#options' => ['-1' => $this->t('All the regions')] + PalomCountry::getRegionListContainNodeTypes($country_id, ['sacred_place']),
                '#default_value' => -1,
                '#ajax' => [
                    'callback' => [$this, 'changeRegion'],
                ]
            ];
        }

        // Output of cities
        $form['city_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'city-wrapper',
            ]
        ];

        if ($form_state->getValue('regions') > 0)
            $geo_id = $form_state->getValue('regions');
        else
            $geo_id = $country_id;

        $cities = PalomPlaces::getCitiesList($geo_id);

        if ($cities!=[]){
            $form['city_wrapper']['cities'] = [
                '#type' => 'checkboxes',
                '#options' => $cities,
                '#ajax' => [
                    'callback' => [$this, 'selectCity'],
                    'progress' => [
                        'type' => 'none',
                    ]
                ]
            ];
        }

        $selected_cities = $this->getSelectedCities($form_state);

        $places = PalomPlaces::getPlaces(0, $element_start + PalomPlaces::getCountDefault(), $geo_id, $selected_cities);

        $places_count = PalomPlaces::getPlacesCount($geo_id, $selected_cities);

        $form['es_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'es-wrapper'
            ]
        ];

        $form['es_wrapper']['es'] = [
            '#type' => 'markup',
            '#markup' => '',
        ];

        $form['places'] = [
            '#type' => 'markup',
            '#markup' => render($places),
            '#prefix' => '<div id="places"><ul id="ul-places">',
            '#suffix' => '</ul></div>',
        ];

        $form['btn_add_more_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'btn-add-more-wrapper',
            ]
        ];

        // Add a button "addMode" in case of undisplayed elements
        if ($places_count > $element_start + PalomPlaces::getCountDefault()){
            $form['btn_add_more_wrapper']['btn_add_more'] = [
                '#type' => 'button',
                '#value' =>  'Еще ...',
                '#ajax' => [
                    'callback' => [$this, 'addMore'],
                    'event' => 'mouseup',
                    'progress' => [
                        'type' => 'none',
                    ]
                ]
            ];
        }

        $trigger = $form_state->getTriggeringElement();
        if (isset($trigger['#ajax']['callback'])) {
            if ($trigger['#attributes']['data-drupal-selector'] === 'edit-btn-add-more')
                $element_start += PalomPlaces::getCountDefault();
            else
                $element_start = 0;
        };
        $form_state->set('element_start', $element_start);

        $form_state->setCached(FALSE);

        $form['#attached']['library'][] = 'palom_places/palom_places';

        return $form;
    }

    public function getFormId(){
        return 'places_form';
    }

    public function submitForm(array &$form, FormStateInterface $form_state){
        $form_state->setRebuild();
    }

    // Set up region and cities upon changing a country + output of sacred places
    public function changeCountry(array &$form, FormStateInterface $form_state){

        $ajax_responce = new AjaxResponse();

        $regions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($form_state->getValue('country'));

        if ($regions!=[])
            $ajax_responce->addCommand(new HtmlCommand('#region-wrapper', Ajax::preRenderAjaxForm($form['region_wrapper']['regions'])));
        else
            $ajax_responce->addCommand(new HtmlCommand('#region-wrapper', ''));

        $ajax_responce->addCommand(new HtmlCommand('#city-wrapper', Ajax::preRenderAjaxForm($form['city_wrapper']['cities'])));
        $ajax_responce->addCommand(new HtmlCommand('#ul-places', PalomPlaces::getPlaces(0, PalomPlaces::getCountDefault(), $form_state->getValue('country'), [])));
        $ajax_responce->addCommand(new HtmlCommand('#btn-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_add_more_wrapper']['btn_add_more'])));

        return $ajax_responce;
    }

    // Set up cities upon changing a region + output of sacred places
    public function changeRegion(array &$form, FormStateInterface $form_state){
        $geo_id = $form_state->getValue('regions');
        if ($geo_id == -1)
            $geo_id = $form_state->getValue('country');

        $ajax_responce = new AjaxResponse();

        $ajax_responce->addCommand(new HtmlCommand('#city-wrapper', Ajax::preRenderAjaxForm($form['city_wrapper']['cities'])));
        $ajax_responce->addCommand(new HtmlCommand('#ul-places', PalomPlaces::getPlaces(0, PalomPlaces::getCountDefault(), $geo_id, [])));
        $ajax_responce->addCommand(new HtmlCommand('#btn-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_add_more_wrapper']['btn_add_more'])));

        return $ajax_responce;
    }

    // Returns a current country or region
    protected function getCurrentGeo($form_state){
        $country_id = $form_state->getValue('country');
        $region_id = $form_state->getValue('regions');

        if ((isset($region_id)) && ($region_id > -1))
            $geo_id = $region_id;
        else
            $geo_id = $country_id;

        return $geo_id;
    }

    // A list of selected cities
    protected function getSelectedCities(FormStateInterface $form_state){
        $cities = $form_state->getValue('cities');
        $city_ids = [];
        if ($cities!=[]){
            foreach($cities as $city){
                if ($city>0){
                    $city_ids[] = $city;
                }
            }
        }
        return $city_ids;
    }

    // Changing the list of sacred places upon choose of the city list
    public function selectCity(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

        $geo_id = $this->getCurrentGeo($form_state);
        $city_ids = $this->getSelectedCities($form_state);

        $ajax_responce->addCommand(new HtmlCommand('#ul-places', PalomPlaces::getPlaces(0, PalomPlaces::getCountDefault(), $geo_id, $city_ids)));
        $ajax_responce->addCommand(new HtmlCommand('#btn-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_add_more_wrapper']['btn_add_more'])));

        return $ajax_responce;
    }

    //  Load more elements
    public function addMore(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

        if ($form_state->get('element_start') !== NULL)
            $element_start = $form_state->get('element_start');
        else
            $element_start = 0;

        $geo_id = $this->getCurrentGeo($form_state);
        $city_ids = $this->getSelectedCities($form_state);

        $ajax_responce->addCommand(new AppendCommand('#ul-places', PalomPlaces::getPlaces($element_start, PalomPlaces::getCountDefault(), $geo_id, $city_ids)));
        $ajax_responce->addCommand(new HtmlCommand('#btn-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_add_more_wrapper']['btn_add_more'])));

        return $ajax_responce;
    }

}



