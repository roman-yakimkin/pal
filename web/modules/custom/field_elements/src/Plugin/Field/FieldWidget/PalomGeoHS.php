<?php

namespace Drupal\field_elements\Plugin\Field\FieldWidget;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\HTMLCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Ajax;
use Drupal\Core\Render\Element\RenderElement;
use Drupal\field_elements\FieldWidgets;
use Drupal\palom_country\PalomCountry;
use Drupal\palom_libs\PalomGeo;
use Drupal\palom_places\PalomPlaces;

/**
 * Plugin implementation of the 'field_palom_geo_hs' widget.
 *
 * @FieldWidget(
 *   id = "field_palom_geo_hs",
 *   module = "field_elements",
 *   label = @Translation("Palom Geo Country and Region Hierarchical Select widget"),
 *   field_types = {
 *     "field_palom_geo"
 *   }
 * )
 */
class PalomGeoHS extends WidgetBase {

  public $region_wrapper_name;
  public $field_name;

  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state)
  {
    $this->field_name = $this->fieldDefinition->getName();
    $this->region_wrapper_name = 'region-ajax-wrapper-'.$this->field_name.'-'.$delta;

    $input_data = $form_state->getUserInput();

    $values = $items->getValue();

    // TODO: Implement formElement() method.
    $te = $form_state->getTriggeringElement();

    if (($te['#name'] == "$this->field_name[$delta][country_id]")){
      $country_id = $input_data[$this->field_name][0]['country_id'];
//      $region_id = $input_data[$this->field_name][0]['region_wrapper']['region_id'] ?? 0;
      $region_id = 0;
    }
    else
    if (isset($values[$delta]['country_id'])){
      $country_id = $values[$delta]['country_id'];
      $region_id = $values[$delta]['region_id'];
    }
    else{
      $country_id = PalomGeo::getCountryDefault();
      $region_id = 0;
    }

    $element['country_id'] = [
      '#type' => 'select',
      '#title' => t('Select a country'),
      '#options' => PalomGeo::getCountryList(),
      '#default_value' => $country_id,
      '#ajax' => [
        'callback' => [$this, '\Drupal\field_elements\Plugin\Field\FieldWidget\PalomGeoHS::changeCountry'],
        'wrapper' => $this->region_wrapper_name,
        'method' => 'html',
        'progress' => [
          'type' => 'none',
        ]
      ]
    ];

    $element['region_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => $this->region_wrapper_name,
      ]
    ];

    // Вывод регионов
    $regions = PalomGeo::getRegionList($country_id);

    if ($regions!=[]){
      $element['region_wrapper']['region_id'] = [
        '#type' => 'select',
        '#title' => t('Select a region'),
        '#options' => $regions,
        '#empty_option'  => t('-select-'),
        '#empty_value' => 0,
        '#default_value' => $region_id,
        '#required' => TRUE,
      ];
    }
    else{
      $element['region_wrapper']['region_id'] = [
        '#type' => 'hidden',
        '#value' => 0,
      ];
    }

    return $element;
  }

  public function massageFormValues(array $values, array $form, FormStateInterface $form_state)
  {
    $ret_values = [];
    foreach ($values as $one_value){
      $ret_values[] = [
        'country_id' => $one_value['country_id'],
        'region_id' => $one_value['region_wrapper']['region_id'],
      ];
    }
    return $ret_values;
  }

  // Отображение списка регионов в некоторых случаях
  public function changeCountry(array &$form, FormStateInterface $form_state){
/*
    $country_id = $form_state->getValue('field_geo')[0]['country_id'];

    $ajax_responce = new AjaxResponse();

//    $ajax_responce->addCommand(new )

    $regions = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadChildren($country_id);

//    if ($regions!=[])
      $ajax_responce->addCommand(new HTMLCommand('#'.$this->region_wrapper_name, Ajax::preRenderAjaxForm($form[$this->field_name]['widget'][0]['region_wrapper']['region_id'])));
//    else
//      $ajax_responce->addCommand(new HtmlCommand('#'.$this->region_wrapper_name, ''));

    return $ajax_responce;
*/
    return $form[$this->field_name]['widget'][0]['region_wrapper']['region_id'];
  }
}