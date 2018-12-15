<?php

namespace Drupal\field_elements\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\WidgetInterface;
use Drupal\Core\Form\FormStateInterface;

/**
* A widget bar.
*
* @FieldWidget(
*   id = "yrv_multi_date",
*   label = @Translation("YRV Multi Date"),
*   field_types = {
*     "datetime",
*   },
*   multiple_values = TRUE
* )
*/
class YRVMultiDateWidget extends WidgetBase {

    /**
     * {@inheritdoc}
     */
    public static function defaultSettings() {
        return [
            'display_label' => TRUE,
        ] + parent::defaultSettings();
    }

    /**
     * {@inheritdoc}
     */
    public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state){
        $element = [];

        $field_label = $this->fieldDefinition->getLabel();
        $field_description = $this->fieldDefinition->getDescription();

        $yrv_dates = [];

        // Массив дат для передачи в виджет
        foreach ($items->getValue() as $one_date) {
            $yrv_dates[] = $one_date['value'];
        }

        $field_name = $this->fieldDefinition->getName();

        $yrv_multi_date_widget_settings = [
            $field_name => [
                'dates' => $yrv_dates,
                'cardinality' => $items->getFieldDefinition()->getFieldStorageDefinition()->getCardinality(),
            ],
        ];

        $element['dates'] = [
            '#type' => 'fieldset',
            '#tree' => TRUE,
            '#title' => $field_label,
            '#description' => $field_description,
        ];

        $element['dates']['dates_array'] = [
            '#type' => 'hidden',
        ];

        $element['dates']['dates_widget'] = array(
            '#type' => 'theme',
            '#theme' => 'yrv_multi_date_widget',
            '#field_name' => $field_name,
        );

        // Add a library and thansmit data
        $element['#attached']['library'][] = 'field_elements/yrv_multi_date';
        $element['#attached']['drupalSettings']['yrv_multi_date_widget'] = $yrv_multi_date_widget_settings;

        return $element;
    }

    public function massageFormValues(array $values, array $form, FormStateInterface $form_state){
        $ret_values = [];

        $stDates = trim($values['dates']['dates_array']);
        $dates = explode(" ", $stDates);
        if ($stDates != '') {
            foreach ($dates as $one_date) {
                $ret_values[] = [
                    'value' => $one_date,
                ];
            };
        }

        return $ret_values;
    }
}

