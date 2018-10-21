<?php

namespace Drupal\field_elements\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\palom_libs\PalomLibs;

/**
 * Plugin implementation of the 'yrv_full_city_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "yrv_full_city_formatter",
 *   module = "field_elements",
 *   label = @Translation("YRV Full City Formatter"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class YRVFullCityFormatter extends FormatterBase {

    public static function defaultSettings(){
        $options = parent::defaultSettings();

        $options['city'] = 'text_link_all';
        $options['city_type'] = 'text_only';
        $options['country'] = 'text_only';
        $options['region'] = 'text_only';

        return $options;
    }

    public function viewElements(FieldItemListInterface $items, $langcode){
        $elements = [];

        $settings = $this->getSettings();

        foreach ($items->getValue() as $delta => $item) {

            $city_id = $item['target_id'];

            $city_info_fields = [];
            if ($settings['city'] == 'text_link_all'){
                $city_info_fields = ['all'];
            }
            else{
                if ($settings['city_type'] == 'text_link')
                    $city_info_fields[] = 'type';
                if ($settings['city'] == 'text_link')
                    $city_info_fields[] = 'city';
                if ($settings['country'] == 'text_link')
                    $city_info_fields[] = 'geo';
                if ($settings['region'] == 'text_link' && !in_array('geo', $city_info_fields))
                    $city_info_fields[] = 'geo';
            }

            $city_info = PalomLibs::getCityInfo($city_id, $city_info_fields);

            $elements[$delta] = [
                '#type' => 'markup',
                '#markup' => $city_info['city_display'],
            ];

        }

        return $elements;
    }

    public function settingsForm(array $form, FormStateInterface $form_state){
        $form = parent::settingsForm($form, $form_state);

        $form['city'] = [
            '#type' => 'select',
            '#title' => t('City display mode'),
            '#options' => [
                'text_only' => t('Text only'),
                'text_link' => t('Text and link on city name'),
                'text_link_all' => t('Text and link over all'),
            ],
            '#default_value' => $this->getSetting('city'),
        ];

        $form['city_type'] = [
            '#type' => 'select',
            '#title' => t('City type display mode'),
            '#options' => [
                'text_only' => t('Text only'),
                'text_link' => t('Text and link'),
            ],
            '#default_value' => $this->getSetting('city_type'),
        ];

        $form['country'] = [
            '#type' => 'select',
            '#title' => t('Country display mode'),
            '#options' => [
                'text_only' => t('Text only'),
                'text_link' => t('Text and link'),
            ],
            '#default_value' => $this->getSetting('country'),
        ];

        $form['region'] = [
            '#type' => 'select',
            '#title' => t('Region display mode'),
            '#options' => [
                'text_only' => t('Text only'),
                'text_link' => t('Text and link'),
            ],
            '#default_value' => $this->getSetting('region'),
        ];

        return $form;
    }

    public function settingsSummary(){
        $summary = [];
        $settings = $this->getSettings();

        $summary[] = t('City display mode: @mode', ['@mode' => $settings['city']]);
        $summary[] = t('City type display mode: @mode', ['@mode' => $settings['city_type']]);
        $summary[] = t('Country display mode: @mode', ['@mode' => $settings['country']]);
        $summary[] = t('Region display mode: @mode', ['@mode' => $settings['region']]);

        return $summary;
    }

}