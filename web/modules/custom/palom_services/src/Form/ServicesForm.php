<?php

namespace Drupal\palom_services\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Ajax;
use Drupal\palom_country\PalomCountry;
use Drupal\palom_places\PalomPlaces;
use Drupal\palom_services\PalomServices;


class ServicesForm extends FormBase {

    private $service_type;

    // The form constructor
    public function __constructor($_service_type){
        $this->service_type = $_service_type;
    }

    // The form building
    public function buildForm(array $form, FormStateInterface $form_state, $args = NULL){
        $this->service_type = $args;

        if (!isset($this->service_type))
            $this->service_type = PalomServices::getServiceTypes();

        if ($form_state->get('services_geo_start') !== NULL )
            $services_geo_start = $form_state->get('services_geo_start');
        else
            $services_geo_start = 0;

        if ($form_state->get('services_by_place_start') !== NULL )
            $services_by_place_start = $form_state->get('services_by_place_start');
        else
            $services_by_place_start = 0;

        if ($form_state->get('services_by_place_count') != NULL )
            $services_by_place_count = $form_state->get('services_by_place_count');
        else
            $services_by_place_count = 0;

        $trigger = $form_state->getTriggeringElement();

        if (!empty($form_state->getValue('country')))
            $country_id = $form_state->getValue('country');
        else
            $country_id = PalomServices::getCountryDefault();

        $form['autocomplete_places'] = [
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'palom_services.autocomplete',
            '#ajax' => [
                'callback' => '::autocompleteGetPlaces',
                'event' => 'autocompleteclose',
            ]
        ];

        $form['country'] = [
            '#type' => 'select',
            '#options' => PalomCountry::getCountryListContainNodeType($this->service_type),
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
                '#options' => ['-1' => $this->t('All the regions')] + PalomCountry::getRegionListContainNodeTypes($country_id, $this->service_type),
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

        $cities = PalomServices::getCitiesList($geo_id, $this->service_type);

        if ($cities!=[]){
            $form['city_wrapper']['cities'] = [
                '#type' => 'checkboxes',
                '#options' => $cities,
                '#ajax' => [
                    'callback' => '::selectCity',
                    'progress' => [
                        'type' => 'none',
                    ]
                ]
            ];
        }

        $selected_cities = $this->getSelectedCities($form_state);

        $services_geo = PalomServices::getServicesGeo($this->service_type, 0, $services_geo_start + PalomServices::getCountDefault(), $geo_id, $selected_cities);

        $services_geo_count = PalomServices::getServicesGeoCount($this->service_type, $geo_id, $selected_cities);


        $form['services_geo'] = [
            '#type' => 'markup',
            '#markup' => render($services_geo),
            '#prefix' => '<div id="services-geo"><ul id="ul-services-geo">',
            '#suffix' => '</ul></div>',
        ];

        $form['btn_services_geo_add_more_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'btn-services-geo-add-more-wrapper',
            ]
        ];

        // In case of undisplayed objects we add a button "Add more"
        if ($services_geo_count > $services_geo_start + PalomServices::getCountDefault()+1){
            $form['btn_services_geo_add_more_wrapper']['btn_services_geo_add_more'] = [
                '#type' => 'button',
                '#value' =>  $this->t('More ...'),
                '#name' => 'btn_services_geo_add_more',
                '#ajax' => [
                    'callback' => [$this, 'servicesGeoAddMore'],
                    'event' => 'mouseup',
                    'progress' => [
                        'type' => 'none',
                    ]
                ]
            ];
        }

        $services_by_place = '';

        $form['services_by_place'] = [
            '#type' => 'markup',
            '#markup' => render($services_by_place),
            '#prefix' => '<div id="services-by-place"><ul id="ul-services-by-place">',
            '#suffix' => '</ul></div>',
        ];

        $form['btn_services_by_place_add_more_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'btn-services-by-place-add-more-wrapper',
            ]
        ];

        // If the sacred place was selected via autocomplete
        if (isset($trigger['#attributes']['data-drupal-selector']) && ($trigger['#attributes']['data-drupal-selector'] === 'edit-autocomplete-places')){
            $place_id = $this->getAutocompletePlaceId($form_state);
            $services_by_place_count = PalomServices::getServicesByPlaceCount($this->service_type, $place_id);
            $services_by_place_start = 0;
        }

        $form['es_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'es-wrapper',
            ]
        ];

        $form['es_wrapper']['es'] = [
//            '#markup' => $services_geo_count.' - '.$services_geo_start. ' - '.PalomServices::getCountDefault(),
            '#markup' => $services_by_place_count.' - '.$services_by_place_start. ' - '.PalomServices::getCountDefault(),
//            '#markup' => $ttt['#attributes']['data-drupal-selector'],
        ];

        if (isset($trigger['#ajax']['callback'])) {

            // If the button "Add more" is pressed, then recalc the first value of element
            if ($trigger['#attributes']['data-drupal-selector'] === 'edit-btn-services-geo-add-more')
                $services_geo_start += PalomServices::getCountDefault();
            else
                $services_geo_start = 0;

            if ($trigger['#attributes']['data-drupal-selector'] === 'edit-btn-services-by-place-add-more')
                $services_by_place_start += PalomServices::getCountDefault();
            else
                $services_by_place_start = 0;
        };

        // In case of undisplayed elementd add a button "Add more"
        if ($services_by_place_count > $services_by_place_start + PalomServices::getCountDefault()) {
            $form['btn_services_by_place_add_more_wrapper']['btn_services_by_place_add_more'] = [
                '#type' => 'button',
                '#value' =>  $this->t('More ...'),
                '#name' => 'btn_services_by_place_add_more',
                '#ajax' => [
                    'callback' => '::servicesByPlaceAddMore',
                    'event' => 'mouseup',
                    'progress' => [
                        'type' => 'none',
                    ]
                ]
            ];
        }

        $form_state->set('services_geo_start', $services_geo_start);
        $form_state->set('services_by_place_start', $services_by_place_start);
        $form_state->set('services_by_place_count', $services_by_place_count);

        $ttt = $form_state->getTriggeringElement();

        $form_state->setCached(FALSE);

        $form['#attached']['library'][] = 'palom_services/palom_services';

        return $form;
    }

    public function getFormId(){
        return 'services_form';
    }

    public function submitForm(array &$form, FormStateInterface $form_state){
//        $form_state->setRebuild();
    }

    protected function getAutocompletePlaceId($form_state){
        $value = $form_state->getValue('autocomplete_places');
        preg_match("/([0-9]+)/", $value, $matches);
        return $matches[0];
    }

    // Output the list of sacred places by autocomplete function
    public function autocompleteGetPlaces(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

        $place_id = $this->getAutocompletePlaceId($form_state);

        $services = PalomServices::getServicesByPlace($this->service_type, 0, PalomServices::getCountDefault(), $place_id);

        $ajax_responce->addCommand(new HtmlCommand('#ul-services-by-place', $services));

        $ajax_responce->addCommand(new HtmlCommand('#es-wrapper', Ajax::preRenderAjaxForm($form['es_wrapper']['es'])));

        $ajax_responce->addCommand(new HtmlCommand('#btn-services-by-place-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_by_place_add_more_wrapper']['btn_services_by_place_add_more'])));

        return $ajax_responce;
    }

    // Set regions and cities upon changing a country + output the companies
    public function changeCountry(array &$form, FormStateInterface $form_state){

        $ajax_responce = new AjaxResponse();

        $regions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($form_state->getValue('country'));

        if ($regions!=[])
            $ajax_responce->addCommand(new HtmlCommand('#region-wrapper', Ajax::preRenderAjaxForm($form['region_wrapper']['regions'])));
        else
            $ajax_responce->addCommand(new HtmlCommand('#region-wrapper', ''));

        $ajax_responce->addCommand(new HtmlCommand('#city-wrapper', Ajax::preRenderAjaxForm($form['city_wrapper']['cities'])));
        $ajax_responce->addCommand(new HtmlCommand('#ul-services-geo', PalomServices::getServicesGeo($this->service_type, 0, PalomServices::getCountDefault(), $form_state->getValue('country'), [])));

        $ajax_responce->addCommand(new HtmlCommand('#btn-services-geo-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_geo_add_more_wrapper']['btn_services_geo_add_more'])));

        return $ajax_responce;
    }

    // Set cities upon changing a region + output the companies
    public function changeRegion(array &$form, FormStateInterface $form_state){

        $geo_id = $form_state->getValue('regions');
        if ($geo_id == -1)
            $geo_id = $form_state->getValue('country');

        $ajax_responce = new AjaxResponse();

        $ajax_responce->addCommand(new HtmlCommand('#city-wrapper', Ajax::preRenderAjaxForm($form['city_wrapper']['cities'])));
        $ajax_responce->addCommand(new HtmlCommand('#ul-services-geo', PalomServices::getServicesGeo($this->service_type, 0, PalomServices::getCountDefault(), $geo_id, [])));

        $ajax_responce->addCommand(new HtmlCommand('#btn-services-geo-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_geo_add_more_wrapper']['btn_services_geo_add_more'])));

        return $ajax_responce;
    }

    // Returns the current country of the region
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


    // Changing of the company list upon selection of the cities list
    public function selectCity(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

        $country_id = $form_state->getValue('country');
        $region_id = $form_state->getValue('regions');

        if ((isset($region_id)) && ($region_id > -1))
            $geo_id = $region_id;
        else
            $geo_id = $country_id;

        $cities = $form_state->getValue('cities');
        $city_ids = [];
        foreach($cities as $city){
            if ($city>0){
                $city_ids[] = $city;
            }
        }

        $ajax_responce->addCommand(new HtmlCommand('#ul-services-geo', PalomServices::getServicesGeo($this->service_type, 0, PalomServices::getCountDefault(), $geo_id, $city_ids)));

        $ajax_responce->addCommand(new HtmlCommand('#btn-services-geo-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_geo_add_more_wrapper']['btn_services_geo_add_more'])));

        return $ajax_responce;
    }

    // Upload more elements
    public function servicesGeoAddMore(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

        if ($form_state->get('services_geo_start') !== NULL)
            $element_start = $form_state->get('services_geo_start');
        else
            $element_start = 0;

        $geo_id = $this->getCurrentGeo($form_state);
        $city_ids = $this->getSelectedCities($form_state);

        $ajax_responce->addCommand(new AppendCommand('#ul-services-geo', PalomServices::getServicesGeo($this->service_type, $element_start, PalomServices::getCountDefault(), $geo_id, $city_ids)));
        $ajax_responce->addCommand(new HtmlCommand('#btn-services-geo-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_geo_add_more_wrapper']['btn_services_geo_add_more'])));

        $ajax_responce->addCommand(new HtmlCommand('#es-wrapper', Ajax::preRenderAjaxForm($form['es_wrapper']['es'])));

        return $ajax_responce;
    }

    // Upload more elements
    public function servicesByPlaceAddMore(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

        if ($form_state->get('services_by_place_start') !== NULL)
            $element_start = $form_state->get('services_by_place_start');
        else
            $element_start = 0;

        $place_id = $this->getAutocompletePlaceId($form_state);

        $services = PalomServices::getServicesByPlace($this->service_type, $element_start, PalomServices::getCountDefault(), $place_id);
        $ajax_responce->addCommand(new AppendCommand('#ul-services-by-place', $services));
        $ajax_responce->addCommand(new HtmlCommand('#btn-services-by-place-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_by_place_add_more_wrapper']['btn_services_by_place_add_more'])));

        $ajax_responce->addCommand(new HtmlCommand('#es-wrapper', Ajax::preRenderAjaxForm($form['es_wrapper']['es'])));


        return $ajax_responce;
    }

}