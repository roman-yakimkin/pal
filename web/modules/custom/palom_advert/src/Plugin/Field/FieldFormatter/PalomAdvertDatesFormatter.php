<?php

namespace Drupal\palom_advert\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'palom_advert_dates' formatter.
 *
 * @FieldFormatter(
 *   id = "palom_advert_dates",
 *   module = "palom_advert",
 *   label = @Translation("Palom Advert Dates"),
 *   field_types = {
 *     "datetime"
 *   }
 * )
 */
class PalomAdvertDatesFormatter extends FormatterBase {

    public function viewElements(FieldItemListInterface $items, $langcode){
        $elements = [];

        foreach ($items->getValue() as $delta => $item) {

            $val = $item['value'];

            if ($val == '2500-01-01')
                $val = 'По заявке';
            else
            if ($val == '2600-01-01')
                $val = 'По мере комплектования группы';
            else
                $val = \DateTime::createFromFormat('Y-m-d',$val)->format('d.m.Y');

            $elements[$delta] = [
                '#type' => 'html_tag',
                '#tag' => 'span',
                '#value' => $val,
            ];

        }

        return $elements;
    }
}