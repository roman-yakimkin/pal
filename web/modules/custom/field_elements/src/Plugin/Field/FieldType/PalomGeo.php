<?php

namespace Drupal\field_elements\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'palom_field_geo' field type.
 *
 * @FieldType(
 *   id = "field_palom_geo",
 *   label = @Translation("Palom geo country and region"),
 *   module = "field_elements",
 *   description = @Translation("Palom geo address with country and region"),
 *   default_widget = "field_palom_geo_hs",
 *   default_formatter = "field_palom_geo_text"
 * )
 */
class PalomGeo extends FieldItemBase {

  public static function schema(FieldStorageDefinitionInterface $field_definition)
  {
    // TODO: Implement schema() method.
    return [
      'columns' => [
        'country_id' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
        'region_id' => [
          'type' => 'int',
          'not null' => FALSE,
        ],
      ],
      'indexes' => [
        'country_id' => ['country_id'],
        'region_id' => ['region_id'],
      ]
    ];
  }

  public function isEmpty()
  {
    $country_id = $this->get('country_id')->getValue();
    $region_id = $this->get('region_id')->getValue();

    return ($country_id === NULL || $country_id === 0) && ($region_id === NULL || $region_id === 0);
  }

  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
  {
    // TODO: Implement propertyDefinitions() method.
    $properties['country_id'] = DataDefinition::create('integer')->setLabel(t('Country'));
    $properties['region_id'] = DataDefinition::create('integer')->setLabel(t('Region'));

    return $properties;
  }

}