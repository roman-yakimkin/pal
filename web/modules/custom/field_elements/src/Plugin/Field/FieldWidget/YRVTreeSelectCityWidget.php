<?php

namespace Drupal\field_elements\Plugin\Field\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field_elements\FieldWidgets;
use Drupal\node\Entity\Node;

/**
 * A widget bar.
 *
 * @FieldWidget(
 *   id = "yrv_tree_select_city",
 *   label = @Translation("YRV Tree Select City"),
 *   field_types = {
 *     "entity_reference",
 *   },
 *   multiple_values = TRUE
 * )
 */

class YRVTreeSelectCityWidget extends WidgetBase {

    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state){

        $element = [];

        $field_label = $this->fieldDefinition->getLabel();
        $field_description = $this->fieldDefinition->getDescription();
        $field_name = $this->fieldDefinition->getName();

        $countries = FieldWidgets::getCountryList();

        $objects = [];

        // Массив гео-объектов для передачи в виджет
        foreach ($items->getValue() as $one_object) {
            if (isset($one_object['target_id'])) {
                $object = Node::load($one_object['target_id']);

                $cr = FieldWidgets::getGeoByCity($one_object['target_id']);

                $objects[] = [
                    'elem_id' => $one_object['target_id'],
                    'name' => $object->get('title')->getString() . ' ' . $cr[0]['geo_str'],
                ];
            }
        }

        $yrv_tree_select_city_widget = [
            'countries' => $countries,
            'field_name' => $field_name,
            $field_name => [
                'objects' => $objects,
                'cardinality' => $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality(),
            ]
        ];

        $element['cities'] = [
            '#type' => 'fieldset',
            '#tree' => TRUE,
            '#title' => $field_label,
            '#description' => $field_description,
        ];

        $element['cities']['values'] = [
            '#type' => 'hidden',
        ];

        $element['cities']['widget'] = array(
            '#type' => 'theme',
            '#theme' => 'yrv_tree_select_city_widget',
            '#field_name' => $field_name,
        );

        $element['#attached']['library'][] = 'field_elements/yrv_tree_select_city';
        $element['#attached']['drupalSettings']['yrv_tree_select_city_widget'] = $yrv_tree_select_city_widget;

        return $element;
    }

    public function massageFormValues(array $values, array $form, FormStateInterface $form_state){
        $ret_values = [];

        $stObjects = trim($values['cities']['values']);
        $objects = explode(" ", $stObjects);
        if ($stObjects != '') {
            foreach ($objects as $delta => $one_obj) {
                $ret_values[$delta]['target_id'] = $one_obj;
            };
        }

        return $ret_values;
    }

}