<?php

namespace Drupal\palom_advert\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Render\Element\Ajax;

class AdvertForm extends FormBase {

    function buildForm(array $form, FormStateInterface $form_state){

        $form['autocomplete_to'] = [
            '#type' => 'textfield',
            '#autocomplete_route_name' => 'palom_advert.autocomplete_to',
            '#ajax' => [
                'callback' => '::autocompleteTo',
                'event' => 'autocompleteclose',
            ]
        ];

        $form['test_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'id' => 'test-wrapper',
            ],
        ];

        $form['test_wrapper']['test'] = [
            '#markup' => $form_state->getValue('autocomplete_to'),
        ];

        $form_state->setCached(FALSE);

        return $form;
    }

    function getFormId(){
        return 'advert_form';
    }

    function submitForm(array &$form, FormStateInterface $form_state){

    }

    // Вывод списка святых мест по функции автозавершения
    public function autocompleteTo(array &$form, FormStateInterface $form_state){
        $ajax_responce = new AjaxResponse();

//        $place_id = $this->getAutocompletePlaceId($form_state);

//        $services = PalomServices::getServicesByPlace($this->service_type, 0, PalomServices::getCountDefault(), $place_id);

//        $ajax_responce->addCommand(new HtmlCommand('#ul-services-by-place', $services));

        $ajax_responce->addCommand(new HtmlCommand('#test-wrapper', Ajax::preRenderAjaxForm($form['test_wrapper']['test'])));

//        $ajax_responce->addCommand(new HtmlCommand('#btn-services-by-place-add-more-wrapper', Ajax::preRenderAjaxForm($form['btn_services_by_place_add_more_wrapper']['btn_services_by_place_add_more'])));

        return $ajax_responce;
    }

}